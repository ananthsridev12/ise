// ============================================================
// PitchPlaybook.gs — Maps every signal type to a pitch angle,
//   key SolidPro services, and a 3-bullet talk track.
//
//   Call setupPitchPlaybook() once from ISE > Outreach menu,
//   then edit the sheet as needed.  lookup() is called
//   automatically during _processItems().
// ============================================================

var PitchPlaybook = (function() {

  var _cache = null;   // { signalKeyLower: { pitchAngle, keyServices, talkTrack, priority } }

  var HEADERS = [
    'PlaybookID', 'Signal', 'Category', 'WhyItMatters',
    'PitchAngle', 'KeyServices', 'TalkTrack1', 'TalkTrack2', 'TalkTrack3', 'Priority'
  ];

  // ----------------------------------------------------------
  // PLAYBOOK DATA  (pre-populated; user can edit sheet values)
  // Columns: signal, category, whyItMatters, pitchAngle, keyServices,
  //          talkTrack1, talkTrack2, talkTrack3, priority
  // ----------------------------------------------------------
  var PLAYBOOK = [
    // ---- Growth & Expansion ----
    {
      signal: 'Capacity Expansion', category: 'Growth & Expansion',
      why: 'Adding capacity creates immediate demand for PLM, DFM, and workflow automation to manage the engineering load.',
      pitch: 'Scaling capacity means scaling your engineering processes — we keep product data and production workflows in sync.',
      t1: 'We help you deploy PLM to handle multi-site product data without duplication.',
      t2: 'Our DFM and DFA services optimize designs for your expanded production lines.',
      t3: 'Workflow automation ensures ECNs and approvals don\'t slow down your ramp-up.',
      services: 'PLM Implementation, DFM/DFA, Workflow Automation, Digital Thread',
      priority: 'High'
    },
    {
      signal: 'New Facility', category: 'Growth & Expansion',
      why: 'A new plant is a clean-slate opportunity to standardize systems before legacy habits take root.',
      pitch: 'New facility = perfect moment to standardize PLM, documentation, and digital systems from day one.',
      t1: 'We implement PLM so your new plant\'s engineering data is structured from the start.',
      t2: 'Our technical publications team delivers operator and maintenance manuals ready for commissioning.',
      t3: 'ERP and CAD integration avoids the data silo problem that plagues most greenfield ramp-ups.',
      services: 'PLM Implementation, CAD Integration, Technical Publications, ERP Integration',
      priority: 'High'
    },
    {
      signal: 'Greenfield Project', category: 'Growth & Expansion',
      why: 'Greenfield projects have zero legacy debt — the ideal window to implement Digital Thread and PLM correctly.',
      pitch: 'Greenfield is the best time to implement PLM and Digital Thread — no legacy to migrate, no politics to navigate.',
      t1: 'We design PLM architecture for your new site before a single part number is created.',
      t2: 'Digital Thread enablement connects design, manufacturing, and service data from day one.',
      t3: 'New product development support helps you get from Gate 1 to Gate 7 faster.',
      services: 'PLM, Digital Thread Enablement, NPD, CAD Integration',
      priority: 'High'
    },
    {
      signal: 'Plant Upgrade', category: 'Growth & Expansion',
      why: 'Plant modernization creates demand for retrofit engineering, updated technical docs, and automation integration.',
      pitch: 'Plant upgrades need retrofit engineering expertise and updated work instructions — we deliver both.',
      t1: 'Our retrofit and sustenance engineering teams handle design modernization without full redesign.',
      t2: 'Updated AR/VR work instructions reduce errors and training time post-upgrade.',
      t3: 'PLM migration moves your existing product data to the new toolset cleanly.',
      services: 'Retrofit Engineering, Sustenance Engineering, PLM Migration, AR/VR Work Instructions',
      priority: 'High'
    },
    {
      signal: 'Warehouse Expansion', category: 'Growth & Expansion',
      why: 'Warehouse expansion indicates operational scale-up, driving ERP and CPQ alignment needs.',
      pitch: 'Warehouse expansion is a trigger to upgrade your inventory, ERP, and fulfillment systems simultaneously.',
      t1: 'ERP integration ensures your warehouse expansion connects seamlessly with engineering and sales systems.',
      t2: 'CPQ alignment prevents pricing errors when your product catalogue grows with the facility.',
      t3: 'Workflow automation reduces manual steps in inbound/outbound order processing.',
      services: 'ERP Integration, CPQ, Workflow Automation',
      priority: 'Medium'
    },
    {
      signal: 'Export Expansion', category: 'Growth & Expansion',
      why: 'Entering new markets requires localized, compliant technical documentation — a common bottleneck.',
      pitch: 'Expanding globally means your product documentation needs to work in every market — we handle S1000D and multi-language localization.',
      t1: 'Our technical authoring teams produce localized manuals, parts catalogues, and safety docs.',
      t2: 'S1000D and DITA-based authoring allows reuse across markets, reducing localization cost.',
      t3: 'We support 40+ languages and all major international compliance standards.',
      services: 'Translation & Localization, S1000D Authoring, Technical Publications, DITA Publishing',
      priority: 'Medium'
    },
    {
      signal: 'New Market Entry', category: 'Growth & Expansion',
      why: 'Market entry requires product adaptation, localized documentation, and sometimes full product re-engineering.',
      pitch: 'Entering a new market often means adapting your product and documentation for local requirements — we do both.',
      t1: 'Product adaptation engineering ensures compliance with local standards and regulations.',
      t2: 'Localized technical publications, IETP, and AR/VR work instructions accelerate market entry.',
      t3: 'Our DFM and DFA reviews reduce cost-of-goods for competitive pricing in new markets.',
      services: 'Translation & Localization, Technical Publications, NPD, DFM/DFA',
      priority: 'Medium'
    },

    // ---- Technology Adoption ----
    {
      signal: 'ERP Implementation', category: 'Technology Adoption',
      why: 'ERP go-lives create an immediate need to integrate CAD, PLM, and engineering data — or risk permanent data silos.',
      pitch: 'ERP implementation is the window to integrate your CAD and PLM — avoid data silos before they become permanent.',
      t1: 'We integrate CAD systems with ERP so BOMs, part numbers, and engineering changes stay in sync.',
      t2: 'Engineering data migration ensures your existing product data lands cleanly in the new ERP.',
      t3: 'CPQ connects your product configurator to the new ERP pricing engine from day one.',
      services: 'ERP Integration, CAD Integration, Engineering Data Migration, CPQ',
      priority: 'High'
    },
    {
      signal: 'SAP Implementation', category: 'Technology Adoption',
      why: 'SAP S/4HANA rollouts are high-risk engineering data integration projects — specialist support reduces that risk.',
      pitch: 'SAP S/4HANA rollouts are our specialty for engineering data integration — we reduce integration risk significantly.',
      t1: 'We connect SAP with your CAD and PLM tools so engineering changes flow automatically.',
      t2: 'Engineering data migration to SAP is our core competency — clean, validated, auditable.',
      t3: 'Our Industrial CPQ integration with SAP automates configure-price-quote for engineer-to-order products.',
      services: 'SAP/ERP Integration, Engineering Data Migration, CAD Integration, Industrial CPQ',
      priority: 'High'
    },
    {
      signal: 'Digital Transformation', category: 'Technology Adoption',
      why: 'Engineering-side digital transformation — CPQ, PLM, Digital Thread, AI — is where most initiatives stall.',
      pitch: 'Engineering digital transformation — CPQ, PLM, Digital Thread, AI-enabled design — is our core focus.',
      t1: 'We implement end-to-end Digital Thread so product data flows from design to service without manual hand-offs.',
      t2: 'AI-enabled engineering solutions reduce design cycle time and surface insights from CAD data.',
      t3: 'CPQ and workflow automation eliminate manual quoting and approval bottlenecks.',
      services: 'CPQ, PLM, Digital Thread, AI-enabled Engineering, Workflow Automation, ALM',
      priority: 'High'
    },
    {
      signal: 'Factory Automation', category: 'Technology Adoption',
      why: 'Automation deployments need embedded systems expertise, technical documentation, and integration support.',
      pitch: 'Factory automation projects need embedded systems and integrated work instructions to scale safely.',
      t1: 'Our special purpose machine and automation team designs and deploys custom automation solutions.',
      t2: 'Embedded software and firmware development for PLCs, SCADA, and industrial controllers.',
      t3: 'AR/VR work instructions reduce operator errors and training time on automated lines.',
      services: 'Special Purpose Machines & Automation, Embedded Software, SCADA/MES, AR/VR Work Instructions',
      priority: 'High'
    },
    {
      signal: 'AI Adoption', category: 'Technology Adoption',
      why: 'AI in engineering accelerates design, simulation, and defect detection — but needs specialist implementation.',
      pitch: 'We build AI-enabled engineering solutions that cut design cycles and reduce rework from the ground up.',
      t1: 'AI-enabled generative design reduces time-to-prototype by surfacing optimal geometries from constraints.',
      t2: 'Computer vision for quality inspection automates defect detection on production lines.',
      t3: 'Predictive analytics from PLM/MES data surfaces maintenance and reliability insights.',
      services: 'AI-enabled Engineering Solutions, Simulation Testing, Computer Vision, PLM',
      priority: 'High'
    },
    {
      signal: 'Smart Factory', category: 'Technology Adoption',
      why: 'Industry 4.0 initiatives need a connected Digital Thread to tie IIoT data back to product design.',
      pitch: 'Smart factory initiatives need a connected Digital Thread — we build that data layer end to end.',
      t1: 'Digital Thread enablement connects your product design, manufacturing, and service data seamlessly.',
      t2: 'IIoT integration feeds real-time production data back into PLM for closed-loop quality improvement.',
      t3: 'AI-enabled engineering uses factory data to continuously improve product and process design.',
      services: 'Digital Thread Enablement, IIoT Integration, AI-enabled Engineering, Cloud Transformation',
      priority: 'High'
    },
    {
      signal: 'Cloud Migration', category: 'Technology Adoption',
      why: 'PLM and ERP cloud migrations are complex engineering data projects — not standard IT migrations.',
      pitch: 'Cloud migration of your PLM and ERP stack requires specialized engineering data expertise — that\'s our niche.',
      t1: 'We manage cloud PLM migrations (Windchill, Teamcenter, Vault) with zero data loss and minimal downtime.',
      t2: 'Cloud transformation strategy ensures your engineering tools are architected for scale and security.',
      t3: 'Post-migration, we connect cloud PLM to SaaS ERP and CPQ for a fully integrated stack.',
      services: 'Cloud Transformation, PLM Migration, Engineering Data Migration, ERP Integration',
      priority: 'High'
    },
    {
      signal: 'IoT Adoption', category: 'Technology Adoption',
      why: 'Industrial IoT deployments need firmware, hardware, and remote monitoring expertise at scale.',
      pitch: 'IoT at scale needs firmware and hardware expertise — plus remote monitoring integration from day one.',
      t1: 'Our embedded software and firmware team develops IoT sensor and gateway software for industrial environments.',
      t2: 'IC Remote Monitoring solutions provide real-time asset visibility with minimal infrastructure overhead.',
      t3: 'Hardware design and test rig development validates IoT devices before field deployment.',
      services: 'IC Remote Monitoring, Firmware Development, Embedded Software, Hardware Design, IC Testing',
      priority: 'High'
    },

    // ---- Financial Events ----
    {
      signal: 'Funding', category: 'Financial Events',
      why: 'Freshly funded companies accelerate product development and digital initiatives — budget is available.',
      pitch: 'Freshly funded companies move fast on product development — we accelerate design, documentation, and digital transformation.',
      t1: 'We scale product engineering teams quickly — NPD, DFM, electronics — without long hiring timelines.',
      t2: 'PLM implementation funded at this stage means your product data scales with your growth.',
      t3: 'Our technical publications team delivers investor-grade product documentation fast.',
      services: 'NPD, PLM, DFM/DFA, Technical Publications, Electronics Engineering',
      priority: 'Medium'
    },
    {
      signal: 'PE Investment', category: 'Financial Events',
      why: 'PE-backed companies face pressure to reduce costs and scale fast — CPQ and automation directly address both.',
      pitch: 'PE-backed companies need to scale fast and cut costs — CPQ and workflow automation deliver both measurably.',
      t1: 'CPQ automates configure-price-quote to shorten sales cycles and eliminate quoting errors.',
      t2: 'Workflow automation removes manual approval bottlenecks in engineering and operations.',
      t3: 'PLM reduces product data rework — a direct cost reduction PE firms can measure.',
      services: 'CPQ, Industrial CPQ, Workflow Automation, PLM',
      priority: 'Medium'
    },
    {
      signal: 'IPO', category: 'Financial Events',
      why: 'IPO preparation requires product data governance, MDM, and audit-ready documentation.',
      pitch: 'IPO preparation means getting your product and engineering data governance in order — auditors will ask.',
      t1: 'Master Data Management ensures your product, BOM, and engineering data is clean and consistent.',
      t2: 'PLM governance gives auditors the traceability they need across your product history.',
      t3: 'Structured technical documentation demonstrates product quality and safety compliance.',
      services: 'MDM, PLM, Technical Publications, Engineering Data Migration',
      priority: 'Low'
    },

    // ---- Hiring & Talent ----
    {
      signal: 'Manufacturing Hiring', category: 'Hiring & Talent',
      why: 'Scaling manufacturing headcount creates demand for standardized work instructions and training materials.',
      pitch: 'When you\'re scaling manufacturing headcount, AR/VR work instructions cut onboarding time and reduce errors dramatically.',
      t1: 'AR/VR work instructions reduce new operator onboarding from weeks to days.',
      t2: 'Standardized operator manuals and IETP ensure every new hire follows the same procedure.',
      t3: 'We create multilingual work instructions for diverse manufacturing workforces.',
      services: 'AR/VR Work Instructions, Technical Authoring, Operator Manuals, IETP',
      priority: 'High'
    },
    {
      signal: 'Technology Hiring', category: 'Hiring & Talent',
      why: 'Tech hiring signals digital ambition — but in-house teams are slow and expensive to build.',
      pitch: 'Instead of building costly tech teams from scratch, we provide on-demand engineering services that scale instantly.',
      t1: 'Embedded software, firmware, and electronics engineering teams available within days.',
      t2: 'AI-enabled engineering services deliver capability without a 12-month hiring runway.',
      t3: 'Digital transformation projects with our team run faster and at lower risk than building in-house.',
      services: 'Embedded Software, AI-enabled Engineering, Electronics Engineering, Digital Transformation',
      priority: 'Medium'
    },
    {
      signal: 'Leadership Hiring', category: 'Hiring & Talent',
      why: 'New engineering or operations leadership drives platform and process decisions — ideal time to introduce SolidPro.',
      pitch: 'New engineering or operations leadership often brings a mandate to modernize — the right time to start a conversation.',
      t1: 'New CTOs and VPs of Engineering almost always review their PLM and CAD tool landscape in the first 90 days.',
      t2: 'We provide a free PLM and engineering data health check to help new leaders understand their baseline.',
      t3: 'Our digital transformation roadmap services help new leaders build a credible 12-month plan.',
      services: 'PLM, Digital Transformation, Engineering Data Migration, CPQ',
      priority: 'Medium'
    },
    {
      signal: 'Bulk Hiring', category: 'Hiring & Talent',
      why: 'High-volume hiring creates an urgent need for scalable, standardized work instructions.',
      pitch: 'High-volume hiring requires standardized, digital work instructions — we build them faster than any in-house team.',
      t1: 'We deliver AR/VR work instructions and IETP at scale, production-ready in 4-6 weeks.',
      t2: 'Structured DITA content means instructions can be updated once and published everywhere.',
      t3: 'Multilingual and regulatory-compliant documentation is built in from the start.',
      services: 'AR/VR Work Instructions, IETP, DITA Publishing, Technical Publications',
      priority: 'High'
    },

    // ---- M&A Activity ----
    {
      signal: 'Acquisition', category: 'M&A Activity',
      why: 'Post-acquisition, engineering data consolidation — merging BOMs, CAD libraries, and PLM instances — is the first major challenge.',
      pitch: 'Post-acquisition engineering data consolidation is the first major IT challenge — we specialize in PLM mergers and data migration.',
      t1: 'PLM consolidation merges two product data environments without losing history or traceability.',
      t2: 'CAD data migration normalizes file formats, naming conventions, and part numbers across the combined entity.',
      t3: 'Master Data Management governance prevents duplicate data problems from multiplying post-acquisition.',
      services: 'PLM Migration, Engineering Data Migration, MDM, CAD Integration',
      priority: 'High'
    },
    {
      signal: 'Merger', category: 'M&A Activity',
      why: 'Mergers create complex, multi-system engineering data integration challenges that delay synergies.',
      pitch: 'Mergers create complex CAD and PLM integration challenges across organizations — we\'ve solved this at scale.',
      t1: 'We map and migrate engineering data from both legacy environments into a unified PLM instance.',
      t2: 'CAD integration across different tools (SolidWorks, CATIA, Creo) is our specialty.',
      t3: 'MDM ensures merged BOMs, supplier data, and product records are clean from day one.',
      services: 'PLM Migration, Engineering Data Migration, MDM, CAD Integration',
      priority: 'High'
    },
    {
      signal: 'Joint Venture', category: 'M&A Activity',
      why: 'JVs require secure, controlled product data sharing across two organizations.',
      pitch: 'Joint ventures need secure product data sharing across organizations — Digital Thread and PLM make it controlled and auditable.',
      t1: 'Digital Thread enablement creates a secure, role-based data sharing layer between JV partners.',
      t2: 'PLM federation allows both organizations to maintain independence while collaborating on shared projects.',
      t3: 'CAD integration ensures both design teams can work in their native tools without file format friction.',
      services: 'Digital Thread, PLM, CAD Integration, Engineering Change Management',
      priority: 'High'
    },
    {
      signal: 'Divestiture', category: 'M&A Activity',
      why: 'Divestitures require surgical separation of engineering data — a technically complex, high-stakes migration.',
      pitch: 'Divestitures require careful, audited separation of engineering data — we run these migrations cleanly.',
      t1: 'We extract the divested business unit\'s PLM data while ensuring the parent entity retains what it needs.',
      t2: 'Engineering data migration creates a standalone, fully functional system for the divested entity.',
      t3: 'Data governance documentation satisfies legal and audit requirements for clean separation.',
      services: 'Engineering Data Migration, PLM Migration, MDM',
      priority: 'Medium'
    },

    // ---- Market Activity ----
    {
      signal: 'Product Launch', category: 'Market Activity',
      why: 'Product launches require complete technical documentation — manuals, IETP, parts catalogues — delivered on deadline.',
      pitch: 'Product launches need comprehensive technical documentation on deadline — manuals, IETP, AR/VR — we deliver.',
      t1: 'We build operator manuals, service manuals, and parts catalogues from your CAD and BOM data.',
      t2: 'Interactive IETP and AR/VR work instructions are increasingly expected at product launch.',
      t3: 'Our structured DITA authoring means documentation updates as the product evolves, not just at launch.',
      services: 'Technical Authoring, Operator Manuals, IETP, AR/VR Work Instructions, Parts Catalogues',
      priority: 'Medium'
    },
    {
      signal: 'Partnership', category: 'Market Activity',
      why: 'Strategic partnerships often require secure CAD/PLM data exchange and co-development workflows.',
      pitch: 'Strategic partnerships need secure product data sharing and co-development workflows — Digital Thread enables this.',
      t1: 'Digital Thread allows controlled, audit-logged product data exchange with your partner.',
      t2: 'CAD integration ensures both teams can collaborate without expensive file conversion cycles.',
      t3: 'Engineering Change Management keeps both organizations aligned on design changes during co-development.',
      services: 'Digital Thread, CAD Integration, Engineering Change Management, PLM',
      priority: 'Medium'
    },
    {
      signal: 'New Contract', category: 'Market Activity',
      why: 'Major contract wins create immediate demand for engineering resources and documentation delivery.',
      pitch: 'Major contract wins create immediate engineering resource demands — we scale with you, fast.',
      t1: 'Our NPD and product engineering teams ramp up in days, not months.',
      t2: 'Sustenance engineering keeps existing products compliant while new contract work ramps up.',
      t3: 'Technical publications teams deliver contract-mandated documentation on the customer\'s schedule.',
      services: 'NPD, Sustenance Engineering, Technical Publications, Electronics Engineering',
      priority: 'High'
    },
    {
      signal: 'Procurement Expansion', category: 'Market Activity',
      why: 'Expanding the supplier or customer base creates CPQ and ERP alignment pressure.',
      pitch: 'Procurement expansion is the trigger to modernize your CPQ and supplier data management simultaneously.',
      t1: 'Industrial CPQ handles configure-price-quote for complex, configurable products across an expanded catalogue.',
      t2: 'ERP integration ensures supplier data and pricing flow cleanly into your engineering and sales systems.',
      t3: 'MDM governance prevents duplicate supplier and part data from multiplying with scale.',
      services: 'CPQ, Industrial CPQ, ERP Integration, MDM',
      priority: 'High'
    },
    {
      signal: 'Leadership Change', category: 'Market Activity',
      why: 'New leadership resets technology priorities — a natural entry point for introducing SolidPro services.',
      pitch: 'New leadership means new technology decisions — the right moment to introduce SolidPro as a strategic partner.',
      t1: 'Incoming engineering leaders almost always review PLM, CAD, and ERP tooling in their first 90 days.',
      t2: 'We offer a complimentary digital maturity assessment to help new leaders establish a baseline quickly.',
      t3: 'Our track record in the sector makes us a credible partner for transformation roadmaps.',
      services: 'PLM, Digital Transformation, CPQ, Engineering Data Migration',
      priority: 'Medium'
    },

    // ---- Regulatory & Compliance ----
    {
      signal: 'Regulatory Approval', category: 'Regulatory & Compliance',
      why: 'Regulatory approvals often require structured, auditable technical documentation as a deliverable.',
      pitch: 'Regulatory approvals require rigorous, structured documentation — S1000D, DITA, and IETP are our specialization.',
      t1: 'S1000D authoring delivers aerospace, defense, and industrial documentation to regulatory standards.',
      t2: 'DITA XML publishing ensures documentation is structured, reusable, and audit-ready.',
      t3: 'IETP and interactive manuals speed up regulator review cycles with navigable, structured content.',
      services: 'S1000D Authoring, DITA Publishing, IETP, Technical Publications, Compliance Documentation',
      priority: 'Medium'
    },
    {
      signal: 'Quality Certification', category: 'Regulatory & Compliance',
      why: 'ISO, AS9100, IATF certifications require comprehensive, traceability-linked technical documentation.',
      pitch: 'ISO and AS9100 certification requires rigorous, traceable documentation — S1000D and structured authoring is our expertise.',
      t1: 'DFMEA and PFMEA documentation supports risk-based certification requirements.',
      t2: 'S1000D authoring ensures your technical documents meet aerospace and defence certification standards.',
      t3: 'Structured DITA content allows document control and traceability across the entire product lifecycle.',
      services: 'S1000D Authoring, DITA Publishing, DFMEA, PFMEA, Quality Documentation',
      priority: 'Medium'
    },
    {
      signal: 'Environmental Compliance', category: 'Regulatory & Compliance',
      why: 'ESG and net zero commitments need a dedicated platform to track, measure, and report progress.',
      pitch: 'We have NetZeroHub — a dedicated sustainability platform built for manufacturers tracking net zero commitments.',
      t1: 'NetZeroHub tracks Scope 1, 2, and 3 emissions across your manufacturing operations.',
      t2: 'Sustainability engineering services help identify and reduce emissions in product design and production.',
      t3: 'We deliver ESG reporting documentation aligned with GRI, CDP, and TCFD frameworks.',
      services: 'NetZeroHub, Sustainability Engineering, ESG Reporting',
      priority: 'High'
    },

    // ---- Supply Chain & Logistics ----
    {
      signal: 'Supply Chain Change', category: 'Supply Chain & Logistics',
      why: 'Supply chain transitions — nearshoring, reshoring, China+1 — require ERP and CPQ realignment.',
      pitch: 'Supply chain transitions need your ERP and CPQ realigned to new supplier configurations — we manage that.',
      t1: 'ERP integration connects new supplier data, lead times, and pricing cleanly into your existing systems.',
      t2: 'CPQ reconfiguration reflects new supplier options and pricing without breaking existing product rules.',
      t3: 'Engineering Data Migration handles any tool or data changes that come with new supply chain partners.',
      services: 'ERP Integration, CPQ, Industrial CPQ, Engineering Data Migration',
      priority: 'High'
    },
    {
      signal: 'New Supplier', category: 'Supply Chain & Logistics',
      why: 'Qualifying new suppliers requires updated product configurations, pricing models, and BOM adjustments.',
      pitch: 'New supplier qualification requires updating product configurations and pricing — CPQ and ERP integration make this fast.',
      t1: 'Industrial CPQ handles complex configuration and pricing updates for new supplier variants.',
      t2: 'ERP integration ensures new supplier data flows into engineering and procurement without manual re-entry.',
      t3: 'Value engineering reviews identify where new suppliers create cost-saving design changes.',
      services: 'CPQ, Industrial CPQ, ERP Integration, Value Engineering',
      priority: 'Medium'
    },
    {
      signal: 'Logistics Expansion', category: 'Supply Chain & Logistics',
      why: 'Logistics expansion creates demand for ERP integration and workflow automation to manage increased volume.',
      pitch: 'Logistics expansion needs ERP and workflow systems that scale with your new throughput requirements.',
      t1: 'ERP integration connects new logistics nodes to your engineering and sales systems automatically.',
      t2: 'Workflow automation removes manual steps in order processing and inventory management.',
      t3: 'Technical publications for logistics operations ensure consistent processes across all sites.',
      services: 'ERP Integration, Workflow Automation, Technical Publications',
      priority: 'Medium'
    }
  ];

  // ----------------------------------------------------------
  // PUBLIC
  // ----------------------------------------------------------

  /**
   * Creates the Pitch Playbook sheet and pre-populates it with
   * SolidPro-specific signal-to-pitch mappings.
   * Safe to run again — only adds missing entries.
   */
  function setup() {
    var ws = _getOrCreateSheet();
    var existing = _existingSignals(ws);
    var nextId   = _nextId(ws);
    var added    = 0;

    PLAYBOOK.forEach(function(entry) {
      if (existing[entry.signal.toLowerCase()]) return;
      ws.appendRow([
        nextId++,
        entry.signal,
        entry.category,
        entry.why,
        entry.pitch,
        entry.services,
        entry.t1,
        entry.t2,
        entry.t3,
        entry.priority
      ]);
      added++;
    });

    _cache = null;  // force reload
    logInfo('PitchPlaybook.setup: added ' + added + ' entries (' +
            (PLAYBOOK.length - added) + ' already existed)');
    SpreadsheetApp.getActiveSpreadsheet().toast(
      'Pitch Playbook ready: ' + added + ' entries added.', 'ISE', 5);
  }

  /**
   * Returns the pitch context for a given signal name, or an empty object.
   * Result is cached after first load.
   *
   * @param {string} signalName
   * @returns {{ pitchAngle, keyServices, talkTrack, priority }}
   */
  function lookup(signalName) {
    _ensureLoaded();
    var key = String(signalName || '').toLowerCase().trim();
    return _cache[key] || {};
  }

  /**
   * Invalidates the lookup cache.
   */
  function invalidate() {
    _cache = null;
  }

  // ----------------------------------------------------------
  // PRIVATE
  // ----------------------------------------------------------

  function _ensureLoaded() {
    if (_cache !== null) return;
    _cache = {};
    try {
      var ws = getSpreadsheet().getSheetByName(SHEETS.PITCH_PLAYBOOK);
      if (!ws || ws.getLastRow() < 2) return;
      var rows = ws.getRange(2, 1, ws.getLastRow() - 1, ws.getLastColumn()).getValues();
      rows.forEach(function(r) {
        var sig = String(r[COL.PITCH_PLAYBOOK.SIGNAL - 1] || '').toLowerCase().trim();
        if (!sig) return;
        var talkParts = [
          r[COL.PITCH_PLAYBOOK.TALK_TRACK_1 - 1],
          r[COL.PITCH_PLAYBOOK.TALK_TRACK_2 - 1],
          r[COL.PITCH_PLAYBOOK.TALK_TRACK_3 - 1]
        ].filter(Boolean).join(' | ');

        _cache[sig] = {
          pitchAngle:  String(r[COL.PITCH_PLAYBOOK.PITCH_ANGLE - 1]  || ''),
          keyServices: String(r[COL.PITCH_PLAYBOOK.KEY_SERVICES - 1] || ''),
          talkTrack:   talkParts,
          priority:    String(r[COL.PITCH_PLAYBOOK.PRIORITY - 1]     || 'Medium')
        };
      });
      logDebug('PitchPlaybook: loaded ' + Object.keys(_cache).length + ' entries');
    } catch(e) {
      logWarn('PitchPlaybook._ensureLoaded: ' + e.message);
    }
  }

  function _getOrCreateSheet() {
    var ss = getSpreadsheet();
    var ws = ss.getSheetByName(SHEETS.PITCH_PLAYBOOK);
    if (!ws) {
      ws = ss.insertSheet(SHEETS.PITCH_PLAYBOOK);
      ws.getRange(1, 1, 1, HEADERS.length).setValues([HEADERS]);
      ws.setFrozenRows(1);
    }
    return ws;
  }

  function _existingSignals(ws) {
    var map = {};
    if (ws.getLastRow() < 2) return map;
    var vals = ws.getRange(2, COL.PITCH_PLAYBOOK.SIGNAL,
                           ws.getLastRow() - 1, 1).getValues();
    vals.forEach(function(r) {
      var sig = String(r[0] || '').toLowerCase().trim();
      if (sig) map[sig] = true;
    });
    return map;
  }

  function _nextId(ws) {
    if (ws.getLastRow() < 2) return 1;
    var ids = ws.getRange(2, COL.PITCH_PLAYBOOK.PLAYBOOK_ID,
                          ws.getLastRow() - 1, 1).getValues();
    var max = 0;
    ids.forEach(function(r) {
      var v = parseInt(r[0]);
      if (!isNaN(v) && v > max) max = v;
    });
    return max + 1;
  }

  return { setup: setup, lookup: lookup, invalidate: invalidate };

})();
