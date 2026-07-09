</main>

<div id="toast"></div>

<script>
function toast(msg, type='success') {
  const t = document.getElementById('toast');
  const el = document.createElement('div');
  el.className = 'toast-item ' + type;
  el.textContent = msg;
  t.appendChild(el);
  setTimeout(() => el.remove(), 4000);
}

async function post(url, data={}, isForm=false) {
  const opts = { method: 'POST' };
  if (isForm) { opts.body = data; }
  else { opts.headers = {'Content-Type':'application/json'}; opts.body = JSON.stringify(data); }
  const r = await fetch(url, opts);
  return r.json();
}
</script>
</body>
</html>
