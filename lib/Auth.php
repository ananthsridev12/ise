<?php
/**
 * Auth — session-based authentication for ISE multi-tenant.
 *
 * Session flow:
 *   1. User submits email+password to login.php
 *   2. Auth::login() validates credentials, creates a sessions row, sets ise_session cookie
 *   3. Every page calls Auth::requireLogin() at the top (via layout.php)
 *   4. Auth::user() / Auth::tenantId() return the current session's data
 *
 * Platform admin flow (separate credential store):
 *   Auth::requirePlatformLogin() — used only by platform/ pages
 */
class Auth {

    const COOKIE     = 'ise_session';
    const PLAT_COOKIE = 'ise_platform';
    const DAYS       = 30;

    // ── Tenant user auth ──────────────────────────────────────────────────

    public static function login(string $email, string $password): bool {
        $user = DB::fetchOne(
            'SELECT u.*, t.active AS tenant_active FROM users u
             JOIN tenants t ON t.id = u.tenant_id
             WHERE u.email = ? AND u.active = 1 AND t.active = 1',
            [strtolower(trim($email))]
        );
        if (!$user || !password_verify($password, $user['password_hash'])) return false;

        $token   = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', time() + self::DAYS * 86400);
        DB::insert('sessions', [
            'token'      => $token,
            'user_id'    => $user['id'],
            'tenant_id'  => $user['tenant_id'],
            'expires_at' => $expires,
        ]);

        setcookie(self::COOKIE, $token, [
            'expires'  => time() + self::DAYS * 86400,
            'path'     => '/',
            'httponly' => true,
            'samesite' => 'Lax',
            'secure'   => isset($_SERVER['HTTPS']),
        ]);
        $_SESSION['_auth_user'] = null; // clear cache
        return true;
    }

    public static function logout(): void {
        if ($token = $_COOKIE[self::COOKIE] ?? '') {
            DB::query('DELETE FROM sessions WHERE token = ?', [$token]);
            setcookie(self::COOKIE, '', ['expires' => time() - 3600, 'path' => '/', 'httponly' => true]);
        }
        session_destroy();
    }

    public static function session(): ?array {
        static $cache = false;
        if ($cache !== false) return $cache;
        $token = $_COOKIE[self::COOKIE] ?? '';
        if (!$token) { $cache = null; return null; }
        $row = DB::fetchOne(
            'SELECT s.*, u.email, u.display_name, u.role, u.active AS user_active,
                    t.name AS tenant_name, t.slug AS tenant_slug, t.active AS tenant_active
             FROM sessions s
             JOIN users u ON u.id = s.user_id
             JOIN tenants t ON t.id = s.tenant_id
             WHERE s.token = ? AND s.expires_at > NOW() AND u.active = 1 AND t.active = 1',
            [$token]
        );
        $cache = $row ?: null;
        return $cache;
    }

    public static function user(): ?array   { return self::session(); }
    public static function tenantId(): ?int { $s = self::session(); return $s ? (int)$s['tenant_id'] : null; }
    public static function isAdmin(): bool  { $s = self::session(); return $s && $s['role'] === 'admin'; }

    public static function requireLogin(string $redirect = '/login.php'): void {
        if (!self::session()) {
            header('Location: ' . $redirect);
            exit;
        }
    }

    public static function requireAdmin(): void {
        self::requireLogin();
        if (!self::isAdmin()) {
            http_response_code(403);
            die('<h2 style="font-family:sans-serif;padding:40px">Access denied — admin only.</h2>');
        }
    }

    // ── Platform admin auth ───────────────────────────────────────────────

    public static function platformLogin(string $email, string $password): bool {
        $admin = DB::fetchOne('SELECT * FROM platform_admins WHERE email = ?', [strtolower(trim($email))]);
        if (!$admin || !password_verify($password, $admin['password_hash'])) return false;

        $token   = bin2hex(random_bytes(32));
        $expires = time() + 8 * 3600; // 8 hour platform session
        setcookie(self::PLAT_COOKIE, $token, [
            'expires'  => $expires,
            'path'     => '/platform/',
            'httponly' => true,
            'samesite' => 'Strict',
            'secure'   => isset($_SERVER['HTTPS']),
        ]);
        $_SESSION['_platform_token'] = $token;
        $_SESSION['_platform_exp']   = $expires;
        $_SESSION['_platform_email'] = $admin['email'];
        return true;
    }

    public static function platformLogout(): void {
        setcookie(self::PLAT_COOKIE, '', ['expires' => time() - 3600, 'path' => '/platform/', 'httponly' => true]);
        unset($_SESSION['_platform_token'], $_SESSION['_platform_exp'], $_SESSION['_platform_email']);
    }

    public static function isPlatformAdmin(): bool {
        $token = $_SESSION['_platform_token'] ?? '';
        $exp   = $_SESSION['_platform_exp']   ?? 0;
        return $token && $exp > time() && $token === ($_COOKIE[self::PLAT_COOKIE] ?? '');
    }

    public static function requirePlatformLogin(): void {
        if (!self::isPlatformAdmin()) {
            header('Location: /platform/login.php');
            exit;
        }
    }

    // ── Helper to hash a password (used by setup/user creation) ──────────

    public static function hashPassword(string $password): string {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }

    // ── Member KB permissions ─────────────────────────────────────────────

    public static function allowedVerticalIds(): array {
        $s = self::session();
        if (!$s) return [];
        if ($s['role'] === 'admin') {
            $rows = DB::fetchAll('SELECT id FROM kb_verticals WHERE tenant_id = ?', [$s['tenant_id']]);
            return array_column($rows, 'id');
        }
        $rows = DB::fetchAll('SELECT vertical_id FROM user_kb_verticals WHERE user_id = ?', [$s['user_id']]);
        return array_column($rows, 'vertical_id');
    }

    public static function allowedServiceIds(): array {
        $s = self::session();
        if (!$s) return [];
        if ($s['role'] === 'admin') {
            $rows = DB::fetchAll('SELECT id FROM kb_services WHERE tenant_id = ?', [$s['tenant_id']]);
            return array_column($rows, 'id');
        }
        $rows = DB::fetchAll('SELECT service_id FROM user_kb_services WHERE user_id = ?', [$s['user_id']]);
        return array_column($rows, 'service_id');
    }
}
