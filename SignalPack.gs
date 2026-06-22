// ============================================================
// SignalPack.gs — Pre-built Categories, Signals, and Keywords
//   covering B2B manufacturing and industrial sales intent.
//
//   Call setupSignalPack() from ISE > Setup > Load Signal Pack
//   or run it directly in the Apps Script editor.
//
//   The function is additive: it skips entries that already
//   exist by name, so it is safe to run more than once.
// ============================================================

var SignalPack = (function() {

  // ----------------------------------------------------------
  // CATEGORIES
  // ----------------------------------------------------------
  var CATEGORIES = [
    { name: 'Growth & Expansion',       description: 'Physical growth, new facilities, capacity increases, and geographic expansion' },
    { name: 'Technology Adoption',      description: 'ERP, automation, digital transformation, AI, and smart factory initiatives' },
    { name: 'Financial Events',         description: 'Funding rounds, PE investment, and IPO activity' },
    { name: 'Hiring & Talent',          description: 'Manufacturing hiring, tech hiring, leadership appointments, and bulk recruitment' },
    { name: 'M&A Activity',             description: 'Acquisitions, mergers, joint ventures, and divestitures' },
    { name: 'Market Activity',          description: 'Partnerships, new contracts, product launches, and procurement expansion' },
    { name: 'Regulatory & Compliance',  description: 'Certifications, government approvals, and environmental compliance' },
    { name: 'Supply Chain & Logistics', description: 'Supply chain changes, new suppliers, logistics expansion, and reshoring' }
  ];

  // ----------------------------------------------------------
  // SIGNALS  { name, category (must match CATEGORIES.name),
  //             score, buyingIntent }
  // ----------------------------------------------------------
  var SIGNALS = [
    // Growth & Expansion
    { name: 'Capacity Expansion',     category: 'Growth & Expansion',       score: 70, buyingIntent: true  },
    { name: 'New Facility',           category: 'Growth & Expansion',       score: 75, buyingIntent: true  },
    { name: 'Greenfield Project',     category: 'Growth & Expansion',       score: 80, buyingIntent: true  },
    { name: 'Plant Upgrade',          category: 'Growth & Expansion',       score: 65, buyingIntent: true  },
    { name: 'Warehouse Expansion',    category: 'Growth & Expansion',       score: 65, buyingIntent: true  },
    { name: 'Export Expansion',       category: 'Growth & Expansion',       score: 50, buyingIntent: false },
    { name: 'New Market Entry',       category: 'Growth & Expansion',       score: 55, buyingIntent: false },

    // Technology Adoption
    { name: 'ERP Implementation',     category: 'Technology Adoption',      score: 80, buyingIntent: true  },
    { name: 'SAP Implementation',     category: 'Technology Adoption',      score: 80, buyingIntent: true  },
    { name: 'Digital Transformation', category: 'Technology Adoption',      score: 70, buyingIntent: true  },
    { name: 'Factory Automation',     category: 'Technology Adoption',      score: 75, buyingIntent: true  },
    { name: 'AI Adoption',            category: 'Technology Adoption',      score: 65, buyingIntent: true  },
    { name: 'Smart Factory',          category: 'Technology Adoption',      score: 70, buyingIntent: true  },
    { name: 'Cloud Migration',        category: 'Technology Adoption',      score: 60, buyingIntent: true  },
    { name: 'IoT Adoption',           category: 'Technology Adoption',      score: 65, buyingIntent: true  },

    // Financial Events
    { name: 'Funding',                category: 'Financial Events',         score: 55, buyingIntent: false },
    { name: 'PE Investment',          category: 'Financial Events',         score: 60, buyingIntent: false },
    { name: 'IPO',                    category: 'Financial Events',         score: 50, buyingIntent: false },

    // Hiring & Talent
    { name: 'Manufacturing Hiring',   category: 'Hiring & Talent',          score: 65, buyingIntent: true  },
    { name: 'Technology Hiring',      category: 'Hiring & Talent',          score: 60, buyingIntent: true  },
    { name: 'Leadership Hiring',      category: 'Hiring & Talent',          score: 45, buyingIntent: false },
    { name: 'Bulk Hiring',            category: 'Hiring & Talent',          score: 65, buyingIntent: true  },

    // M&A Activity
    { name: 'Acquisition',            category: 'M&A Activity',             score: 55, buyingIntent: false },
    { name: 'Merger',                 category: 'M&A Activity',             score: 50, buyingIntent: false },
    { name: 'Joint Venture',          category: 'M&A Activity',             score: 65, buyingIntent: true  },
    { name: 'Divestiture',            category: 'M&A Activity',             score: 45, buyingIntent: false },

    // Market Activity
    { name: 'Product Launch',         category: 'Market Activity',          score: 50, buyingIntent: false },
    { name: 'Partnership',            category: 'Market Activity',          score: 50, buyingIntent: false },
    { name: 'New Contract',           category: 'Market Activity',          score: 65, buyingIntent: true  },
    { name: 'Procurement Expansion',  category: 'Market Activity',          score: 75, buyingIntent: true  },
    { name: 'Leadership Change',      category: 'Market Activity',          score: 40, buyingIntent: false },

    // Regulatory & Compliance
    { name: 'Regulatory Approval',    category: 'Regulatory & Compliance',  score: 50, buyingIntent: false },
    { name: 'Quality Certification',  category: 'Regulatory & Compliance',  score: 55, buyingIntent: true  },
    { name: 'Environmental Compliance', category: 'Regulatory & Compliance', score: 50, buyingIntent: true  },

    // Supply Chain & Logistics
    { name: 'Supply Chain Change',    category: 'Supply Chain & Logistics', score: 60, buyingIntent: true  },
    { name: 'New Supplier',           category: 'Supply Chain & Logistics', score: 65, buyingIntent: true  },
    { name: 'Logistics Expansion',    category: 'Supply Chain & Logistics', score: 60, buyingIntent: true  }
  ];

  // ----------------------------------------------------------
  // KEYWORDS  { keyword, signal (must match SIGNALS.name),
  //             weight (1-3), matchType ('exact'|'contains') }
  // ----------------------------------------------------------
  var KEYWORDS = [
    // Capacity Expansion
    { keyword: 'capacity expansion',     signal: 'Capacity Expansion',     weight: 3, matchType: 'exact'    },
    { keyword: 'capacity increase',      signal: 'Capacity Expansion',     weight: 3, matchType: 'exact'    },
    { keyword: 'production expansion',   signal: 'Capacity Expansion',     weight: 3, matchType: 'contains' },
    { keyword: 'production increase',    signal: 'Capacity Expansion',     weight: 2, matchType: 'contains' },
    { keyword: 'expanding capacity',     signal: 'Capacity Expansion',     weight: 3, matchType: 'contains' },
    { keyword: 'ramp up production',     signal: 'Capacity Expansion',     weight: 3, matchType: 'contains' },
    { keyword: 'scale up',               signal: 'Capacity Expansion',     weight: 2, matchType: 'contains' },
    { keyword: 'doubling capacity',      signal: 'Capacity Expansion',     weight: 3, matchType: 'contains' },
    { keyword: 'additional capacity',    signal: 'Capacity Expansion',     weight: 2, matchType: 'contains' },
    { keyword: 'capacity addition',      signal: 'Capacity Expansion',     weight: 3, matchType: 'contains' },

    // New Facility
    { keyword: 'new plant',              signal: 'New Facility',           weight: 3, matchType: 'exact'    },
    { keyword: 'new factory',            signal: 'New Facility',           weight: 3, matchType: 'exact'    },
    { keyword: 'new facility',           signal: 'New Facility',           weight: 3, matchType: 'exact'    },
    { keyword: 'opens plant',            signal: 'New Facility',           weight: 3, matchType: 'contains' },
    { keyword: 'opens factory',          signal: 'New Facility',           weight: 3, matchType: 'contains' },
    { keyword: 'new manufacturing unit', signal: 'New Facility',           weight: 3, matchType: 'contains' },
    { keyword: 'commissions plant',      signal: 'New Facility',           weight: 3, matchType: 'contains' },
    { keyword: 'new production line',    signal: 'New Facility',           weight: 2, matchType: 'contains' },
    { keyword: 'new plant site',         signal: 'New Facility',           weight: 2, matchType: 'contains' },
    { keyword: 'second plant',           signal: 'New Facility',           weight: 2, matchType: 'contains' },
    { keyword: 'third plant',            signal: 'New Facility',           weight: 2, matchType: 'contains' },

    // Greenfield Project
    { keyword: 'greenfield',             signal: 'Greenfield Project',     weight: 3, matchType: 'exact'    },
    { keyword: 'greenfield project',     signal: 'Greenfield Project',     weight: 3, matchType: 'exact'    },
    { keyword: 'groundbreaking ceremony',signal: 'Greenfield Project',     weight: 3, matchType: 'contains' },
    { keyword: 'lays foundation',        signal: 'Greenfield Project',     weight: 3, matchType: 'contains' },
    { keyword: 'foundation stone',       signal: 'Greenfield Project',     weight: 2, matchType: 'contains' },
    { keyword: 'breaks ground',          signal: 'Greenfield Project',     weight: 3, matchType: 'contains' },
    { keyword: 'ground breaking',        signal: 'Greenfield Project',     weight: 2, matchType: 'contains' },

    // Plant Upgrade
    { keyword: 'plant upgrade',          signal: 'Plant Upgrade',          weight: 3, matchType: 'exact'    },
    { keyword: 'plant modernisation',    signal: 'Plant Upgrade',          weight: 3, matchType: 'contains' },
    { keyword: 'plant modernization',    signal: 'Plant Upgrade',          weight: 3, matchType: 'contains' },
    { keyword: 'modernise plant',        signal: 'Plant Upgrade',          weight: 2, matchType: 'contains' },
    { keyword: 'modernize plant',        signal: 'Plant Upgrade',          weight: 2, matchType: 'contains' },
    { keyword: 'retooling',              signal: 'Plant Upgrade',          weight: 3, matchType: 'exact'    },
    { keyword: 'plant revamp',           signal: 'Plant Upgrade',          weight: 3, matchType: 'contains' },
    { keyword: 'factory refurbishment',  signal: 'Plant Upgrade',          weight: 2, matchType: 'contains' },
    { keyword: 'upgrade facility',       signal: 'Plant Upgrade',          weight: 2, matchType: 'contains' },
    { keyword: 'technology upgrade',     signal: 'Plant Upgrade',          weight: 2, matchType: 'contains' },

    // Warehouse Expansion
    { keyword: 'warehouse expansion',    signal: 'Warehouse Expansion',    weight: 3, matchType: 'exact'    },
    { keyword: 'new warehouse',          signal: 'Warehouse Expansion',    weight: 3, matchType: 'contains' },
    { keyword: 'distribution center',    signal: 'Warehouse Expansion',    weight: 2, matchType: 'contains' },
    { keyword: 'logistics hub',          signal: 'Warehouse Expansion',    weight: 2, matchType: 'contains' },
    { keyword: 'fulfillment center',     signal: 'Warehouse Expansion',    weight: 2, matchType: 'contains' },
    { keyword: 'new distribution',       signal: 'Warehouse Expansion',    weight: 2, matchType: 'contains' },
    { keyword: 'storage expansion',      signal: 'Warehouse Expansion',    weight: 2, matchType: 'contains' },

    // Export Expansion
    { keyword: 'export expansion',       signal: 'Export Expansion',       weight: 3, matchType: 'exact'    },
    { keyword: 'global expansion',       signal: 'Export Expansion',       weight: 2, matchType: 'contains' },
    { keyword: 'international expansion',signal: 'Export Expansion',       weight: 2, matchType: 'contains' },
    { keyword: 'exports to',             signal: 'Export Expansion',       weight: 2, matchType: 'contains' },
    { keyword: 'overseas expansion',     signal: 'Export Expansion',       weight: 2, matchType: 'contains' },
    { keyword: 'expands globally',       signal: 'Export Expansion',       weight: 2, matchType: 'contains' },

    // New Market Entry
    { keyword: 'market entry',           signal: 'New Market Entry',       weight: 3, matchType: 'exact'    },
    { keyword: 'enters market',          signal: 'New Market Entry',       weight: 3, matchType: 'contains' },
    { keyword: 'forays into',            signal: 'New Market Entry',       weight: 3, matchType: 'contains' },
    { keyword: 'new geography',          signal: 'New Market Entry',       weight: 2, matchType: 'contains' },
    { keyword: 'expands to',             signal: 'New Market Entry',       weight: 2, matchType: 'contains' },
    { keyword: 'enters new market',      signal: 'New Market Entry',       weight: 3, matchType: 'contains' },

    // ERP Implementation
    { keyword: 'erp implementation',     signal: 'ERP Implementation',     weight: 3, matchType: 'exact'    },
    { keyword: 'erp deployment',         signal: 'ERP Implementation',     weight: 3, matchType: 'exact'    },
    { keyword: 'enterprise resource planning', signal: 'ERP Implementation', weight: 3, matchType: 'exact' },
    { keyword: 'microsoft dynamics',     signal: 'ERP Implementation',     weight: 3, matchType: 'contains' },
    { keyword: 'epicor',                 signal: 'ERP Implementation',     weight: 3, matchType: 'exact'    },
    { keyword: 'infor erp',              signal: 'ERP Implementation',     weight: 3, matchType: 'exact'    },
    { keyword: 'odoo',                   signal: 'ERP Implementation',     weight: 2, matchType: 'exact'    },
    { keyword: 'ifs erp',                signal: 'ERP Implementation',     weight: 3, matchType: 'exact'    },
    { keyword: 'netsuite',               signal: 'ERP Implementation',     weight: 3, matchType: 'exact'    },
    { keyword: 'erp upgrade',            signal: 'ERP Implementation',     weight: 3, matchType: 'contains' },
    { keyword: 'erp rollout',            signal: 'ERP Implementation',     weight: 3, matchType: 'contains' },

    // SAP Implementation
    { keyword: 'sap implementation',     signal: 'SAP Implementation',     weight: 3, matchType: 'exact'    },
    { keyword: 'sap s/4hana',            signal: 'SAP Implementation',     weight: 3, matchType: 'exact'    },
    { keyword: 's/4hana',                signal: 'SAP Implementation',     weight: 3, matchType: 'exact'    },
    { keyword: 'sap deployment',         signal: 'SAP Implementation',     weight: 3, matchType: 'exact'    },
    { keyword: 'sap upgrade',            signal: 'SAP Implementation',     weight: 3, matchType: 'contains' },
    { keyword: 'sap rollout',            signal: 'SAP Implementation',     weight: 3, matchType: 'contains' },
    { keyword: 'sap erp',                signal: 'SAP Implementation',     weight: 3, matchType: 'exact'    },
    { keyword: 'sap go live',            signal: 'SAP Implementation',     weight: 3, matchType: 'contains' },
    { keyword: 'sap hana',               signal: 'SAP Implementation',     weight: 3, matchType: 'exact'    },

    // Digital Transformation
    { keyword: 'digital transformation', signal: 'Digital Transformation', weight: 3, matchType: 'exact'    },
    { keyword: 'digitalization',         signal: 'Digital Transformation', weight: 3, matchType: 'exact'    },
    { keyword: 'digitization',           signal: 'Digital Transformation', weight: 2, matchType: 'exact'    },
    { keyword: 'digitisation',           signal: 'Digital Transformation', weight: 2, matchType: 'exact'    },
    { keyword: 'digital overhaul',       signal: 'Digital Transformation', weight: 2, matchType: 'contains' },
    { keyword: 'it transformation',      signal: 'Digital Transformation', weight: 2, matchType: 'contains' },
    { keyword: 'going digital',          signal: 'Digital Transformation', weight: 2, matchType: 'contains' },
    { keyword: 'technology modernization', signal: 'Digital Transformation', weight: 2, matchType: 'contains' },

    // Factory Automation
    { keyword: 'factory automation',     signal: 'Factory Automation',     weight: 3, matchType: 'exact'    },
    { keyword: 'industrial automation',  signal: 'Factory Automation',     weight: 3, matchType: 'exact'    },
    { keyword: 'robotics',               signal: 'Factory Automation',     weight: 3, matchType: 'exact'    },
    { keyword: 'automated manufacturing',signal: 'Factory Automation',     weight: 3, matchType: 'contains' },
    { keyword: 'cobot',                  signal: 'Factory Automation',     weight: 3, matchType: 'exact'    },
    { keyword: 'robotic arm',            signal: 'Factory Automation',     weight: 2, matchType: 'contains' },
    { keyword: 'automation solution',    signal: 'Factory Automation',     weight: 2, matchType: 'contains' },
    { keyword: 'plc',                    signal: 'Factory Automation',     weight: 2, matchType: 'exact'    },
    { keyword: 'scada',                  signal: 'Factory Automation',     weight: 3, matchType: 'exact'    },
    { keyword: 'manufacturing execution system', signal: 'Factory Automation', weight: 2, matchType: 'contains' },
    { keyword: 'mes',                    signal: 'Factory Automation',     weight: 2, matchType: 'exact'    },
    { keyword: 'robot deployment',       signal: 'Factory Automation',     weight: 3, matchType: 'contains' },

    // AI Adoption
    { keyword: 'artificial intelligence',signal: 'AI Adoption',            weight: 3, matchType: 'exact'    },
    { keyword: 'ai adoption',            signal: 'AI Adoption',            weight: 3, matchType: 'exact'    },
    { keyword: 'machine learning',       signal: 'AI Adoption',            weight: 3, matchType: 'exact'    },
    { keyword: 'ai implementation',      signal: 'AI Adoption',            weight: 3, matchType: 'contains' },
    { keyword: 'generative ai',          signal: 'AI Adoption',            weight: 3, matchType: 'exact'    },
    { keyword: 'predictive analytics',   signal: 'AI Adoption',            weight: 2, matchType: 'contains' },
    { keyword: 'computer vision',        signal: 'AI Adoption',            weight: 2, matchType: 'exact'    },
    { keyword: 'ai in manufacturing',    signal: 'AI Adoption',            weight: 3, matchType: 'contains' },
    { keyword: 'ai powered',             signal: 'AI Adoption',            weight: 2, matchType: 'contains' },

    // Smart Factory
    { keyword: 'smart factory',          signal: 'Smart Factory',          weight: 3, matchType: 'exact'    },
    { keyword: 'industry 4.0',           signal: 'Smart Factory',          weight: 3, matchType: 'exact'    },
    { keyword: 'iiot',                   signal: 'Smart Factory',          weight: 3, matchType: 'exact'    },
    { keyword: 'industrial internet of things', signal: 'Smart Factory',   weight: 3, matchType: 'exact'   },
    { keyword: 'connected factory',      signal: 'Smart Factory',          weight: 2, matchType: 'exact'    },
    { keyword: 'digital factory',        signal: 'Smart Factory',          weight: 3, matchType: 'exact'    },
    { keyword: 'lights out manufacturing',signal: 'Smart Factory',         weight: 3, matchType: 'contains' },
    { keyword: 'digital twin',           signal: 'Smart Factory',          weight: 3, matchType: 'exact'    },

    // Cloud Migration
    { keyword: 'cloud migration',        signal: 'Cloud Migration',        weight: 3, matchType: 'exact'    },
    { keyword: 'cloud adoption',         signal: 'Cloud Migration',        weight: 2, matchType: 'exact'    },
    { keyword: 'cloud erp',              signal: 'Cloud Migration',        weight: 3, matchType: 'exact'    },
    { keyword: 'moving to cloud',        signal: 'Cloud Migration',        weight: 2, matchType: 'contains' },
    { keyword: 'saas deployment',        signal: 'Cloud Migration',        weight: 2, matchType: 'contains' },
    { keyword: 'cloud transformation',   signal: 'Cloud Migration',        weight: 2, matchType: 'contains' },

    // IoT Adoption
    { keyword: 'iot',                    signal: 'IoT Adoption',           weight: 3, matchType: 'exact'    },
    { keyword: 'internet of things',     signal: 'IoT Adoption',           weight: 3, matchType: 'exact'    },
    { keyword: 'connected devices',      signal: 'IoT Adoption',           weight: 2, matchType: 'contains' },
    { keyword: 'sensor deployment',      signal: 'IoT Adoption',           weight: 2, matchType: 'contains' },
    { keyword: 'smart sensors',          signal: 'IoT Adoption',           weight: 2, matchType: 'contains' },
    { keyword: 'remote monitoring',      signal: 'IoT Adoption',           weight: 2, matchType: 'contains' },
    { keyword: 'iot platform',           signal: 'IoT Adoption',           weight: 3, matchType: 'contains' },

    // Funding
    { keyword: 'series a',               signal: 'Funding',                weight: 3, matchType: 'exact'    },
    { keyword: 'series b',               signal: 'Funding',                weight: 3, matchType: 'exact'    },
    { keyword: 'series c',               signal: 'Funding',                weight: 3, matchType: 'exact'    },
    { keyword: 'investment round',       signal: 'Funding',                weight: 3, matchType: 'contains' },
    { keyword: 'venture capital',        signal: 'Funding',                weight: 2, matchType: 'exact'    },
    { keyword: 'vc funding',             signal: 'Funding',                weight: 3, matchType: 'contains' },
    { keyword: 'secured funding',        signal: 'Funding',                weight: 2, matchType: 'contains' },
    { keyword: 'raises funding',         signal: 'Funding',                weight: 3, matchType: 'contains' },
    { keyword: 'seed funding',           signal: 'Funding',                weight: 2, matchType: 'exact'    },

    // PE Investment
    { keyword: 'private equity',         signal: 'PE Investment',          weight: 3, matchType: 'exact'    },
    { keyword: 'pe investment',          signal: 'PE Investment',          weight: 3, matchType: 'exact'    },
    { keyword: 'pe backed',              signal: 'PE Investment',          weight: 3, matchType: 'contains' },
    { keyword: 'private equity backed',  signal: 'PE Investment',          weight: 3, matchType: 'contains' },
    { keyword: 'buyout',                 signal: 'PE Investment',          weight: 2, matchType: 'exact'    },

    // IPO
    { keyword: 'ipo',                    signal: 'IPO',                    weight: 3, matchType: 'exact'    },
    { keyword: 'initial public offering',signal: 'IPO',                    weight: 3, matchType: 'exact'    },
    { keyword: 'goes public',            signal: 'IPO',                    weight: 3, matchType: 'contains' },
    { keyword: 'stock exchange listing', signal: 'IPO',                    weight: 2, matchType: 'contains' },
    { keyword: 'public offering',        signal: 'IPO',                    weight: 2, matchType: 'contains' },
    { keyword: 'files for ipo',          signal: 'IPO',                    weight: 3, matchType: 'contains' },

    // Manufacturing Hiring
    { keyword: 'manufacturing hiring',   signal: 'Manufacturing Hiring',   weight: 3, matchType: 'exact'    },
    { keyword: 'factory workers',        signal: 'Manufacturing Hiring',   weight: 3, matchType: 'contains' },
    { keyword: 'plant workers',          signal: 'Manufacturing Hiring',   weight: 2, matchType: 'contains' },
    { keyword: 'hiring operators',       signal: 'Manufacturing Hiring',   weight: 2, matchType: 'contains' },
    { keyword: 'hiring technicians',     signal: 'Manufacturing Hiring',   weight: 2, matchType: 'contains' },
    { keyword: 'manufacturing jobs',     signal: 'Manufacturing Hiring',   weight: 2, matchType: 'exact'    },
    { keyword: 'shopfloor hiring',       signal: 'Manufacturing Hiring',   weight: 3, matchType: 'exact'    },
    { keyword: 'plant hiring',           signal: 'Manufacturing Hiring',   weight: 2, matchType: 'contains' },

    // Technology Hiring
    { keyword: 'technology hiring',      signal: 'Technology Hiring',      weight: 3, matchType: 'exact'    },
    { keyword: 'it hiring',              signal: 'Technology Hiring',      weight: 2, matchType: 'contains' },
    { keyword: 'tech talent',            signal: 'Technology Hiring',      weight: 2, matchType: 'contains' },
    { keyword: 'hiring engineers',       signal: 'Technology Hiring',      weight: 2, matchType: 'contains' },
    { keyword: 'hiring data scientists', signal: 'Technology Hiring',      weight: 2, matchType: 'contains' },
    { keyword: 'ai engineers',           signal: 'Technology Hiring',      weight: 2, matchType: 'contains' },
    { keyword: 'software engineers',     signal: 'Technology Hiring',      weight: 2, matchType: 'contains' },

    // Leadership Hiring
    { keyword: 'appoints',               signal: 'Leadership Hiring',      weight: 2, matchType: 'contains' },
    { keyword: 'new ceo',                signal: 'Leadership Hiring',      weight: 3, matchType: 'exact'    },
    { keyword: 'new cto',                signal: 'Leadership Hiring',      weight: 3, matchType: 'exact'    },
    { keyword: 'new cfo',                signal: 'Leadership Hiring',      weight: 3, matchType: 'exact'    },
    { keyword: 'new coo',                signal: 'Leadership Hiring',      weight: 3, matchType: 'exact'    },
    { keyword: 'new managing director',  signal: 'Leadership Hiring',      weight: 2, matchType: 'contains' },
    { keyword: 'named as chief',         signal: 'Leadership Hiring',      weight: 2, matchType: 'contains' },

    // Bulk Hiring
    { keyword: 'bulk hiring',            signal: 'Bulk Hiring',            weight: 3, matchType: 'exact'    },
    { keyword: 'mass hiring',            signal: 'Bulk Hiring',            weight: 3, matchType: 'exact'    },
    { keyword: 'recruitment drive',      signal: 'Bulk Hiring',            weight: 3, matchType: 'exact'    },
    { keyword: 'hiring spree',           signal: 'Bulk Hiring',            weight: 3, matchType: 'exact'    },
    { keyword: 'job creation',           signal: 'Bulk Hiring',            weight: 2, matchType: 'contains' },
    { keyword: 'to create jobs',         signal: 'Bulk Hiring',            weight: 2, matchType: 'contains' },
    { keyword: 'to hire',                signal: 'Bulk Hiring',            weight: 2, matchType: 'contains' },

    // Acquisition
    { keyword: 'acquires',               signal: 'Acquisition',            weight: 3, matchType: 'exact'    },
    { keyword: 'acquisition',            signal: 'Acquisition',            weight: 3, matchType: 'exact'    },
    { keyword: 'acquired by',            signal: 'Acquisition',            weight: 3, matchType: 'contains' },
    { keyword: 'takeover',               signal: 'Acquisition',            weight: 3, matchType: 'exact'    },
    { keyword: 'buys out',               signal: 'Acquisition',            weight: 3, matchType: 'exact'    },
    { keyword: 'acqui-hire',             signal: 'Acquisition',            weight: 3, matchType: 'exact'    },
    { keyword: 'strategic acquisition',  signal: 'Acquisition',            weight: 3, matchType: 'contains' },
    { keyword: 'to acquire',             signal: 'Acquisition',            weight: 2, matchType: 'contains' },

    // Merger
    { keyword: 'merger',                 signal: 'Merger',                 weight: 3, matchType: 'exact'    },
    { keyword: 'merges with',            signal: 'Merger',                 weight: 3, matchType: 'contains' },
    { keyword: 'combined company',       signal: 'Merger',                 weight: 2, matchType: 'contains' },
    { keyword: 'merge',                  signal: 'Merger',                 weight: 2, matchType: 'contains' },
    { keyword: 'merging with',           signal: 'Merger',                 weight: 3, matchType: 'contains' },

    // Joint Venture
    { keyword: 'joint venture',          signal: 'Joint Venture',          weight: 3, matchType: 'exact'    },
    { keyword: 'tie-up',                 signal: 'Joint Venture',          weight: 3, matchType: 'exact'    },
    { keyword: 'co-venture',             signal: 'Joint Venture',          weight: 2, matchType: 'exact'    },
    { keyword: 'forms jv',               signal: 'Joint Venture',          weight: 3, matchType: 'contains' },
    { keyword: 'joint development',      signal: 'Joint Venture',          weight: 2, matchType: 'contains' },

    // Divestiture
    { keyword: 'divests',                signal: 'Divestiture',            weight: 3, matchType: 'exact'    },
    { keyword: 'divestiture',            signal: 'Divestiture',            weight: 3, matchType: 'exact'    },
    { keyword: 'sells division',         signal: 'Divestiture',            weight: 3, matchType: 'contains' },
    { keyword: 'spins off',              signal: 'Divestiture',            weight: 3, matchType: 'exact'    },
    { keyword: 'carve out',              signal: 'Divestiture',            weight: 2, matchType: 'exact'    },

    // Product Launch
    { keyword: 'product launch',         signal: 'Product Launch',         weight: 3, matchType: 'exact'    },
    { keyword: 'new product',            signal: 'Product Launch',         weight: 2, matchType: 'exact'    },
    { keyword: 'new offering',           signal: 'Product Launch',         weight: 2, matchType: 'exact'    },
    { keyword: 'unveils',                signal: 'Product Launch',         weight: 2, matchType: 'contains' },
    { keyword: 'introduces',             signal: 'Product Launch',         weight: 2, matchType: 'contains' },
    { keyword: 'new model',              signal: 'Product Launch',         weight: 2, matchType: 'contains' },

    // Partnership
    { keyword: 'strategic alliance',     signal: 'Partnership',            weight: 3, matchType: 'exact'    },
    { keyword: 'mou',                    signal: 'Partnership',            weight: 3, matchType: 'exact'    },
    { keyword: 'memorandum of understanding', signal: 'Partnership',       weight: 3, matchType: 'exact'   },
    { keyword: 'signs mou',              signal: 'Partnership',            weight: 3, matchType: 'contains' },
    { keyword: 'collaboration agreement',signal: 'Partnership',            weight: 2, matchType: 'exact'    },
    { keyword: 'teaming agreement',      signal: 'Partnership',            weight: 2, matchType: 'exact'    },
    { keyword: 'strategic partnership',  signal: 'Partnership',            weight: 3, matchType: 'exact'    },

    // New Contract
    { keyword: 'new contract',           signal: 'New Contract',           weight: 3, matchType: 'exact'    },
    { keyword: 'wins contract',          signal: 'New Contract',           weight: 3, matchType: 'contains' },
    { keyword: 'wins order',             signal: 'New Contract',           weight: 3, matchType: 'contains' },
    { keyword: 'awarded contract',       signal: 'New Contract',           weight: 3, matchType: 'contains' },
    { keyword: 'secures order',          signal: 'New Contract',           weight: 3, matchType: 'contains' },
    { keyword: 'purchase order',         signal: 'New Contract',           weight: 2, matchType: 'exact'    },
    { keyword: 'order worth',            signal: 'New Contract',           weight: 2, matchType: 'contains' },
    { keyword: 'multi-year contract',    signal: 'New Contract',           weight: 3, matchType: 'contains' },

    // Procurement Expansion
    { keyword: 'procurement expansion',  signal: 'Procurement Expansion',  weight: 3, matchType: 'exact'    },
    { keyword: 'vendor onboarding',      signal: 'Procurement Expansion',  weight: 2, matchType: 'exact'    },
    { keyword: 'supply agreement',       signal: 'Procurement Expansion',  weight: 2, matchType: 'contains' },
    { keyword: 'procurement decision',   signal: 'Procurement Expansion',  weight: 2, matchType: 'contains' },
    { keyword: 'sourcing strategy',      signal: 'Procurement Expansion',  weight: 2, matchType: 'contains' },
    { keyword: 'expanding vendor base',  signal: 'Procurement Expansion',  weight: 2, matchType: 'contains' },

    // Leadership Change
    { keyword: 'resigns',                signal: 'Leadership Change',      weight: 2, matchType: 'contains' },
    { keyword: 'steps down',             signal: 'Leadership Change',      weight: 2, matchType: 'contains' },
    { keyword: 'replaces',               signal: 'Leadership Change',      weight: 2, matchType: 'contains' },
    { keyword: 'management change',      signal: 'Leadership Change',      weight: 2, matchType: 'exact'    },
    { keyword: 'ceo change',             signal: 'Leadership Change',      weight: 2, matchType: 'exact'    },

    // Regulatory Approval
    { keyword: 'regulatory approval',    signal: 'Regulatory Approval',    weight: 3, matchType: 'exact'    },
    { keyword: 'receives approval',      signal: 'Regulatory Approval',    weight: 2, matchType: 'contains' },
    { keyword: 'gets clearance',         signal: 'Regulatory Approval',    weight: 2, matchType: 'contains' },
    { keyword: 'government approval',    signal: 'Regulatory Approval',    weight: 2, matchType: 'contains' },
    { keyword: 'cleared by',             signal: 'Regulatory Approval',    weight: 2, matchType: 'contains' },
    { keyword: 'approved by regulator',  signal: 'Regulatory Approval',    weight: 3, matchType: 'contains' },

    // Quality Certification
    { keyword: 'iso certification',      signal: 'Quality Certification',  weight: 3, matchType: 'exact'    },
    { keyword: 'quality certification',  signal: 'Quality Certification',  weight: 2, matchType: 'exact'    },
    { keyword: 'iso 9001',               signal: 'Quality Certification',  weight: 3, matchType: 'exact'    },
    { keyword: 'iso 14001',              signal: 'Quality Certification',  weight: 2, matchType: 'exact'    },
    { keyword: 'as9100',                 signal: 'Quality Certification',  weight: 3, matchType: 'exact'    },
    { keyword: 'iatf 16949',             signal: 'Quality Certification',  weight: 3, matchType: 'exact'    },
    { keyword: 'certified manufacturer', signal: 'Quality Certification',  weight: 2, matchType: 'contains' },

    // Environmental Compliance
    { keyword: 'environmental compliance', signal: 'Environmental Compliance', weight: 3, matchType: 'exact' },
    { keyword: 'esg',                    signal: 'Environmental Compliance', weight: 2, matchType: 'exact'  },
    { keyword: 'green manufacturing',    signal: 'Environmental Compliance', weight: 2, matchType: 'exact'  },
    { keyword: 'carbon neutral',         signal: 'Environmental Compliance', weight: 2, matchType: 'exact'  },
    { keyword: 'zero emissions',         signal: 'Environmental Compliance', weight: 2, matchType: 'exact'  },
    { keyword: 'sustainability',         signal: 'Environmental Compliance', weight: 2, matchType: 'contains'},
    { keyword: 'net zero',               signal: 'Environmental Compliance', weight: 2, matchType: 'exact'  },

    // Supply Chain Change
    { keyword: 'supply chain change',    signal: 'Supply Chain Change',    weight: 3, matchType: 'exact'    },
    { keyword: 'supply chain transformation', signal: 'Supply Chain Change', weight: 3, matchType: 'contains' },
    { keyword: 'nearshoring',            signal: 'Supply Chain Change',    weight: 3, matchType: 'exact'    },
    { keyword: 'reshoring',              signal: 'Supply Chain Change',    weight: 3, matchType: 'exact'    },
    { keyword: 'friendshoring',          signal: 'Supply Chain Change',    weight: 3, matchType: 'exact'    },
    { keyword: 'china plus one',         signal: 'Supply Chain Change',    weight: 3, matchType: 'exact'    },
    { keyword: 'supply chain disruption',signal: 'Supply Chain Change',    weight: 2, matchType: 'contains' },
    { keyword: 'diversifying supply',    signal: 'Supply Chain Change',    weight: 2, matchType: 'contains' },

    // New Supplier
    { keyword: 'new supplier',           signal: 'New Supplier',           weight: 3, matchType: 'exact'    },
    { keyword: 'supplier qualification', signal: 'New Supplier',           weight: 2, matchType: 'exact'    },
    { keyword: 'vendor selection',       signal: 'New Supplier',           weight: 2, matchType: 'exact'    },
    { keyword: 'approved vendor',        signal: 'New Supplier',           weight: 2, matchType: 'exact'    },
    { keyword: 'supplier diversification', signal: 'New Supplier',         weight: 2, matchType: 'contains' },

    // Logistics Expansion
    { keyword: 'logistics expansion',    signal: 'Logistics Expansion',    weight: 3, matchType: 'exact'    },
    { keyword: 'new depot',              signal: 'Logistics Expansion',    weight: 2, matchType: 'exact'    },
    { keyword: 'new fulfillment',        signal: 'Logistics Expansion',    weight: 2, matchType: 'contains' },
    { keyword: 'last mile delivery',     signal: 'Logistics Expansion',    weight: 2, matchType: 'exact'    },
    { keyword: 'shipping expansion',     signal: 'Logistics Expansion',    weight: 2, matchType: 'contains' },
    { keyword: 'new logistics hub',      signal: 'Logistics Expansion',    weight: 3, matchType: 'contains' }
  ];

  // ----------------------------------------------------------
  // PUBLIC
  // ----------------------------------------------------------

  /**
   * Loads all categories, signals, and keywords into the sheets.
   * Skips entries that already exist by name to avoid duplicates.
   */
  function setup() {
    logInfo('SignalPack: starting setup...');

    var catMap = _ensureCategories();
    var sigMap = _ensureSignals(catMap);
    _ensureKeywords(sigMap);

    // Invalidate caches so the new data takes effect immediately
    SignalClassifier.invalidate();
    if (typeof KeywordMatcher !== 'undefined' && KeywordMatcher.invalidate) {
      KeywordMatcher.invalidate();
    }

    var msg = 'Signal Pack loaded: ' + CATEGORIES.length + ' categories, ' +
              SIGNALS.length + ' signals, ' + KEYWORDS.length + ' keywords.';
    logInfo('SignalPack: ' + msg);
    SpreadsheetApp.getActiveSpreadsheet().toast(msg, 'ISE Signal Pack', 7);
  }

  // ----------------------------------------------------------
  // PRIVATE
  // ----------------------------------------------------------

  function _ensureCategories() {
    var existing = _buildNameMap('CATEGORIES', COL.CATEGORIES.CATEGORY);
    var nextId   = getNextId('CATEGORIES', COL.CATEGORIES.CATEGORY_ID);
    var catMap   = {};  // name -> id (including pre-existing)

    for (var name in existing) catMap[name] = existing[name];

    CATEGORIES.forEach(function(c) {
      var key = c.name.toLowerCase();
      if (!catMap[key]) {
        appendRow('CATEGORIES', [nextId, c.name, c.description]);
        catMap[key] = nextId;
        nextId++;
      }
    });

    return catMap;
  }

  function _ensureSignals(catMap) {
    var existing = _buildNameMap('SIGNALS', COL.SIGNALS.SIGNAL);
    var nextId   = getNextId('SIGNALS', COL.SIGNALS.SIGNAL_ID);
    var sigMap   = {};

    for (var name in existing) sigMap[name] = existing[name];

    SIGNALS.forEach(function(s) {
      var key    = s.name.toLowerCase();
      var catKey = s.category.toLowerCase();
      var catId  = catMap[catKey] || '';

      if (!sigMap[key]) {
        appendRow('SIGNALS', [nextId, s.name, catId, s.score, s.buyingIntent ? 'TRUE' : 'FALSE', 'TRUE']);
        sigMap[key] = nextId;
        nextId++;
      }
    });

    return sigMap;
  }

  function _ensureKeywords(sigMap) {
    var existing = _buildKeywordSet();
    var nextId   = getNextId('KEYWORDS', COL.KEYWORDS.KEYWORD_ID);

    KEYWORDS.forEach(function(k) {
      var sigKey  = k.signal.toLowerCase();
      var sigId   = sigMap[sigKey] || '';
      var kwKey   = k.keyword.toLowerCase() + '|' + sigId;

      if (!existing[kwKey]) {
        appendRow('KEYWORDS', [nextId, k.keyword, sigId, k.weight, k.matchType, 'TRUE']);
        existing[kwKey] = true;
        nextId++;
      }
    });
  }

  /**
   * Builds a map of lowercased name -> ID from a sheet column.
   */
  function _buildNameMap(sheetKey, nameCol) {
    var idCol = 1;
    var map   = {};
    getDataRows(sheetKey).forEach(function(r) {
      var name = String(r[nameCol - 1] || '').toLowerCase().trim();
      var id   = r[idCol - 1];
      if (name) map[name] = id;
    });
    return map;
  }

  /**
   * Builds a set of existing keyword+signalId combos to prevent duplicates.
   */
  function _buildKeywordSet() {
    var set = {};
    getDataRows('KEYWORDS').forEach(function(r) {
      var kw  = String(r[COL.KEYWORDS.KEYWORD - 1] || '').toLowerCase().trim();
      var sid = String(r[COL.KEYWORDS.SIGNAL_ID - 1] || '').trim();
      if (kw) set[kw + '|' + sid] = true;
    });
    return set;
  }

  return { setup: setup };

})();
