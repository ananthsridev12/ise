import { db } from '@/lib/db';
import { categories, signals, keywords } from '@/lib/db/schema';
import { eq } from 'drizzle-orm';

const CATEGORIES = [
  { category: 'Growth & Expansion', description: 'Physical expansion, new facilities, capacity increases' },
  { category: 'Technology Adoption', description: 'ERP, automation, digital transformation, cloud, IoT' },
  { category: 'Financial Events', description: 'Funding rounds, PE investment, IPO' },
  { category: 'Hiring & Talent', description: 'Manufacturing, tech, leadership hiring signals' },
  { category: 'M&A Activity', description: 'Acquisitions, mergers, joint ventures, divestitures' },
  { category: 'Market Activity', description: 'Product launches, partnerships, new contracts' },
  { category: 'Regulatory & Compliance', description: 'Regulatory approvals, certifications, environmental' },
  { category: 'Supply Chain & Logistics', description: 'Supply chain changes, new suppliers, logistics expansion' },
];

const SIGNALS = [
  // Growth & Expansion
  { signal: 'Capacity Expansion', category: 'Growth & Expansion', score: 70, buyingIntent: true },
  { signal: 'New Facility', category: 'Growth & Expansion', score: 75, buyingIntent: true },
  { signal: 'Greenfield Project', category: 'Growth & Expansion', score: 80, buyingIntent: true },
  { signal: 'Plant Upgrade', category: 'Growth & Expansion', score: 65, buyingIntent: true },
  { signal: 'Warehouse Expansion', category: 'Growth & Expansion', score: 65, buyingIntent: true },
  { signal: 'Export Expansion', category: 'Growth & Expansion', score: 50, buyingIntent: false },
  { signal: 'New Market Entry', category: 'Growth & Expansion', score: 55, buyingIntent: false },
  // Technology Adoption
  { signal: 'ERP Implementation', category: 'Technology Adoption', score: 80, buyingIntent: true },
  { signal: 'SAP Implementation', category: 'Technology Adoption', score: 80, buyingIntent: true },
  { signal: 'Digital Transformation', category: 'Technology Adoption', score: 70, buyingIntent: true },
  { signal: 'Factory Automation', category: 'Technology Adoption', score: 75, buyingIntent: true },
  { signal: 'AI Adoption', category: 'Technology Adoption', score: 65, buyingIntent: true },
  { signal: 'Smart Factory', category: 'Technology Adoption', score: 70, buyingIntent: true },
  { signal: 'Cloud Migration', category: 'Technology Adoption', score: 60, buyingIntent: true },
  { signal: 'IoT Adoption', category: 'Technology Adoption', score: 65, buyingIntent: true },
  // Financial Events
  { signal: 'Funding', category: 'Financial Events', score: 55, buyingIntent: false },
  { signal: 'PE Investment', category: 'Financial Events', score: 60, buyingIntent: false },
  { signal: 'IPO', category: 'Financial Events', score: 50, buyingIntent: false },
  // Hiring & Talent
  { signal: 'Manufacturing Hiring', category: 'Hiring & Talent', score: 65, buyingIntent: true },
  { signal: 'Technology Hiring', category: 'Hiring & Talent', score: 60, buyingIntent: true },
  { signal: 'Leadership Hiring', category: 'Hiring & Talent', score: 45, buyingIntent: false },
  { signal: 'Bulk Hiring', category: 'Hiring & Talent', score: 65, buyingIntent: true },
  // M&A Activity
  { signal: 'Acquisition', category: 'M&A Activity', score: 55, buyingIntent: false },
  { signal: 'Merger', category: 'M&A Activity', score: 50, buyingIntent: false },
  { signal: 'Joint Venture', category: 'M&A Activity', score: 65, buyingIntent: true },
  { signal: 'Divestiture', category: 'M&A Activity', score: 45, buyingIntent: false },
  // Market Activity
  { signal: 'Product Launch', category: 'Market Activity', score: 50, buyingIntent: false },
  { signal: 'Partnership', category: 'Market Activity', score: 50, buyingIntent: false },
  { signal: 'New Contract', category: 'Market Activity', score: 65, buyingIntent: true },
  { signal: 'Procurement Expansion', category: 'Market Activity', score: 75, buyingIntent: true },
  { signal: 'Leadership Change', category: 'Market Activity', score: 40, buyingIntent: false },
  // Regulatory & Compliance
  { signal: 'Regulatory Approval', category: 'Regulatory & Compliance', score: 50, buyingIntent: false },
  { signal: 'Quality Certification', category: 'Regulatory & Compliance', score: 55, buyingIntent: true },
  { signal: 'Environmental Compliance', category: 'Regulatory & Compliance', score: 50, buyingIntent: true },
  // Supply Chain & Logistics
  { signal: 'Supply Chain Change', category: 'Supply Chain & Logistics', score: 60, buyingIntent: true },
  { signal: 'New Supplier', category: 'Supply Chain & Logistics', score: 65, buyingIntent: true },
  { signal: 'Logistics Expansion', category: 'Supply Chain & Logistics', score: 60, buyingIntent: true },
];

const KEYWORDS: { keyword: string; signal: string; weight: number; matchType: 'exact' | 'contains' }[] = [
  // Capacity Expansion
  { keyword: 'capacity expansion', signal: 'Capacity Expansion', weight: 3, matchType: 'exact' },
  { keyword: 'expanding capacity', signal: 'Capacity Expansion', weight: 2, matchType: 'contains' },
  { keyword: 'production expansion', signal: 'Capacity Expansion', weight: 2, matchType: 'contains' },
  { keyword: 'capex investment', signal: 'Capacity Expansion', weight: 2, matchType: 'contains' },
  { keyword: 'expand production', signal: 'Capacity Expansion', weight: 2, matchType: 'contains' },
  // Greenfield Project
  { keyword: 'greenfield', signal: 'Greenfield Project', weight: 3, matchType: 'contains' },
  { keyword: 'new manufacturing facility', signal: 'Greenfield Project', weight: 3, matchType: 'exact' },
  { keyword: 'new plant', signal: 'Greenfield Project', weight: 2, matchType: 'exact' },
  { keyword: 'new factory', signal: 'Greenfield Project', weight: 2, matchType: 'exact' },
  { keyword: 'build new plant', signal: 'Greenfield Project', weight: 3, matchType: 'contains' },
  // New Facility
  { keyword: 'opens new facility', signal: 'New Facility', weight: 3, matchType: 'contains' },
  { keyword: 'new production facility', signal: 'New Facility', weight: 3, matchType: 'contains' },
  { keyword: 'inaugurates plant', signal: 'New Facility', weight: 2, matchType: 'contains' },
  { keyword: 'opens facility', signal: 'New Facility', weight: 2, matchType: 'contains' },
  // Plant Upgrade
  { keyword: 'plant upgrade', signal: 'Plant Upgrade', weight: 3, matchType: 'exact' },
  { keyword: 'plant modernization', signal: 'Plant Upgrade', weight: 3, matchType: 'contains' },
  { keyword: 'facility upgrade', signal: 'Plant Upgrade', weight: 2, matchType: 'contains' },
  { keyword: 'retool plant', signal: 'Plant Upgrade', weight: 2, matchType: 'contains' },
  // ERP Implementation
  { keyword: 'erp implementation', signal: 'ERP Implementation', weight: 3, matchType: 'exact' },
  { keyword: 'erp rollout', signal: 'ERP Implementation', weight: 3, matchType: 'contains' },
  { keyword: 'erp deployment', signal: 'ERP Implementation', weight: 3, matchType: 'contains' },
  { keyword: 'erp upgrade', signal: 'ERP Implementation', weight: 2, matchType: 'contains' },
  { keyword: 'enterprise resource planning', signal: 'ERP Implementation', weight: 2, matchType: 'contains' },
  { keyword: 'oracle erp', signal: 'ERP Implementation', weight: 2, matchType: 'contains' },
  { keyword: 'microsoft dynamics', signal: 'ERP Implementation', weight: 2, matchType: 'contains' },
  { keyword: 'infor erp', signal: 'ERP Implementation', weight: 2, matchType: 'contains' },
  { keyword: 'epicor', signal: 'ERP Implementation', weight: 2, matchType: 'contains' },
  // SAP Implementation
  { keyword: 'sap implementation', signal: 'SAP Implementation', weight: 3, matchType: 'exact' },
  { keyword: 'sap s/4hana', signal: 'SAP Implementation', weight: 3, matchType: 'contains' },
  { keyword: 'sap s4hana', signal: 'SAP Implementation', weight: 3, matchType: 'contains' },
  { keyword: 'sap rollout', signal: 'SAP Implementation', weight: 3, matchType: 'contains' },
  { keyword: 'sap deployment', signal: 'SAP Implementation', weight: 2, matchType: 'contains' },
  { keyword: 'sap migration', signal: 'SAP Implementation', weight: 2, matchType: 'contains' },
  // Digital Transformation
  { keyword: 'digital transformation', signal: 'Digital Transformation', weight: 3, matchType: 'exact' },
  { keyword: 'industry 4.0', signal: 'Digital Transformation', weight: 3, matchType: 'contains' },
  { keyword: 'digitalisation', signal: 'Digital Transformation', weight: 2, matchType: 'contains' },
  { keyword: 'digitalization', signal: 'Digital Transformation', weight: 2, matchType: 'contains' },
  { keyword: 'digital initiative', signal: 'Digital Transformation', weight: 2, matchType: 'contains' },
  // Factory Automation
  { keyword: 'factory automation', signal: 'Factory Automation', weight: 3, matchType: 'exact' },
  { keyword: 'industrial automation', signal: 'Factory Automation', weight: 3, matchType: 'exact' },
  { keyword: 'automated manufacturing', signal: 'Factory Automation', weight: 2, matchType: 'contains' },
  { keyword: 'robotics integration', signal: 'Factory Automation', weight: 2, matchType: 'contains' },
  { keyword: 'plc scada', signal: 'Factory Automation', weight: 2, matchType: 'contains' },
  { keyword: 'automated assembly line', signal: 'Factory Automation', weight: 2, matchType: 'contains' },
  // Smart Factory
  { keyword: 'smart factory', signal: 'Smart Factory', weight: 3, matchType: 'exact' },
  { keyword: 'connected factory', signal: 'Smart Factory', weight: 2, matchType: 'contains' },
  { keyword: 'iiot', signal: 'Smart Factory', weight: 2, matchType: 'contains' },
  { keyword: 'manufacturing intelligence', signal: 'Smart Factory', weight: 2, matchType: 'contains' },
  // AI Adoption
  { keyword: 'ai implementation', signal: 'AI Adoption', weight: 3, matchType: 'contains' },
  { keyword: 'artificial intelligence manufacturing', signal: 'AI Adoption', weight: 3, matchType: 'contains' },
  { keyword: 'machine learning manufacturing', signal: 'AI Adoption', weight: 2, matchType: 'contains' },
  { keyword: 'ai-enabled', signal: 'AI Adoption', weight: 2, matchType: 'contains' },
  // Cloud Migration
  { keyword: 'cloud migration', signal: 'Cloud Migration', weight: 3, matchType: 'exact' },
  { keyword: 'cloud transformation', signal: 'Cloud Migration', weight: 2, matchType: 'contains' },
  { keyword: 'move to cloud', signal: 'Cloud Migration', weight: 2, matchType: 'contains' },
  { keyword: 'cloud erp', signal: 'Cloud Migration', weight: 2, matchType: 'contains' },
  // IoT Adoption
  { keyword: 'iot implementation', signal: 'IoT Adoption', weight: 3, matchType: 'contains' },
  { keyword: 'internet of things', signal: 'IoT Adoption', weight: 2, matchType: 'contains' },
  { keyword: 'connected devices', signal: 'IoT Adoption', weight: 2, matchType: 'contains' },
  { keyword: 'remote monitoring', signal: 'IoT Adoption', weight: 2, matchType: 'contains' },
  // Funding
  { keyword: 'series a', signal: 'Funding', weight: 3, matchType: 'contains' },
  { keyword: 'series b', signal: 'Funding', weight: 3, matchType: 'contains' },
  { keyword: 'raises funding', signal: 'Funding', weight: 2, matchType: 'contains' },
  { keyword: 'venture capital', signal: 'Funding', weight: 2, matchType: 'contains' },
  { keyword: 'seed funding', signal: 'Funding', weight: 2, matchType: 'contains' },
  // PE Investment
  { keyword: 'private equity', signal: 'PE Investment', weight: 3, matchType: 'contains' },
  { keyword: 'pe investment', signal: 'PE Investment', weight: 3, matchType: 'contains' },
  { keyword: 'buyout', signal: 'PE Investment', weight: 2, matchType: 'contains' },
  // Manufacturing Hiring
  { keyword: 'hiring manufacturing engineers', signal: 'Manufacturing Hiring', weight: 3, matchType: 'contains' },
  { keyword: 'manufacturing jobs', signal: 'Manufacturing Hiring', weight: 2, matchType: 'contains' },
  { keyword: 'production engineer', signal: 'Manufacturing Hiring', weight: 2, matchType: 'contains' },
  { keyword: 'plm engineer', signal: 'Manufacturing Hiring', weight: 3, matchType: 'contains' },
  { keyword: 'catia engineer', signal: 'Manufacturing Hiring', weight: 3, matchType: 'contains' },
  { keyword: 'solidworks engineer', signal: 'Manufacturing Hiring', weight: 3, matchType: 'contains' },
  // Technology Hiring
  { keyword: 'sap consultant', signal: 'Technology Hiring', weight: 3, matchType: 'contains' },
  { keyword: 'erp consultant', signal: 'Technology Hiring', weight: 3, matchType: 'contains' },
  { keyword: 'cpq developer', signal: 'Technology Hiring', weight: 3, matchType: 'contains' },
  { keyword: 'cpq administrator', signal: 'Technology Hiring', weight: 3, matchType: 'contains' },
  { keyword: 'digital transformation lead', signal: 'Technology Hiring', weight: 3, matchType: 'contains' },
  { keyword: 'automation engineer', signal: 'Technology Hiring', weight: 2, matchType: 'contains' },
  // Acquisition
  { keyword: 'acquires', signal: 'Acquisition', weight: 3, matchType: 'contains' },
  { keyword: 'acquisition of', signal: 'Acquisition', weight: 3, matchType: 'contains' },
  { keyword: 'takeover', signal: 'Acquisition', weight: 2, matchType: 'contains' },
  // Joint Venture
  { keyword: 'joint venture', signal: 'Joint Venture', weight: 3, matchType: 'exact' },
  { keyword: 'forms jv', signal: 'Joint Venture', weight: 3, matchType: 'contains' },
  // New Contract
  { keyword: 'wins contract', signal: 'New Contract', weight: 3, matchType: 'contains' },
  { keyword: 'awarded contract', signal: 'New Contract', weight: 3, matchType: 'contains' },
  { keyword: 'new order', signal: 'New Contract', weight: 2, matchType: 'contains' },
  { keyword: 'secures deal', signal: 'New Contract', weight: 2, matchType: 'contains' },
  // Supply Chain
  { keyword: 'supply chain', signal: 'Supply Chain Change', weight: 2, matchType: 'contains' },
  { keyword: 'new supplier', signal: 'New Supplier', weight: 3, matchType: 'exact' },
  { keyword: 'supplier partnership', signal: 'New Supplier', weight: 2, matchType: 'contains' },
  { keyword: 'logistics expansion', signal: 'Logistics Expansion', weight: 3, matchType: 'exact' },
  { keyword: 'warehouse expansion', signal: 'Warehouse Expansion', weight: 3, matchType: 'exact' },
  // Quality & Compliance
  { keyword: 'iso certification', signal: 'Quality Certification', weight: 3, matchType: 'contains' },
  { keyword: 'quality certification', signal: 'Quality Certification', weight: 3, matchType: 'exact' },
  { keyword: 'environmental compliance', signal: 'Environmental Compliance', weight: 3, matchType: 'exact' },
  { keyword: 'esg', signal: 'Environmental Compliance', weight: 2, matchType: 'contains' },
  { keyword: 'net zero', signal: 'Environmental Compliance', weight: 2, matchType: 'contains' },
];

export async function seedSignalPack(): Promise<{ categories: number; signals: number; keywords: number }> {
  let catCount = 0, sigCount = 0, kwCount = 0;

  // Insert categories
  const catIdMap = new Map<string, number>();
  for (const cat of CATEGORIES) {
    const existing = await db.select().from(categories).where(eq(categories.category, cat.category)).get();
    if (!existing) {
      const [row] = await db.insert(categories).values(cat).returning({ categoryId: categories.categoryId });
      catIdMap.set(cat.category, row.categoryId);
      catCount++;
    } else {
      catIdMap.set(cat.category, existing.categoryId);
    }
  }

  // Insert signals
  const sigIdMap = new Map<string, number>();
  for (const sig of SIGNALS) {
    const categoryId = catIdMap.get(sig.category);
    const existing = await db.select().from(signals).where(eq(signals.signal, sig.signal)).get();
    if (!existing) {
      const [row] = await db.insert(signals).values({
        signal: sig.signal, categoryId: categoryId || null,
        defaultScore: sig.score, buyingIntent: sig.buyingIntent, active: true,
      }).returning({ signalId: signals.signalId });
      sigIdMap.set(sig.signal, row.signalId);
      sigCount++;
    } else {
      sigIdMap.set(sig.signal, existing.signalId);
    }
  }

  // Insert keywords
  for (const kw of KEYWORDS) {
    const signalId = sigIdMap.get(kw.signal);
    const existing = await db.select().from(keywords).where(eq(keywords.keyword, kw.keyword)).get();
    if (!existing) {
      await db.insert(keywords).values({
        keyword: kw.keyword, signalId: signalId || null,
        weight: kw.weight, matchType: kw.matchType, active: true,
      }).run();
      kwCount++;
    }
  }

  return { categories: catCount, signals: sigCount, keywords: kwCount };
}
