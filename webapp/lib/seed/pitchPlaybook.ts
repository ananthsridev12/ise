import { db } from '@/lib/db';
import { pitchPlaybook } from '@/lib/db/schema';
import { eq } from 'drizzle-orm';

const PLAYBOOK = [
  {
    signal: 'Greenfield Project', category: 'Growth & Expansion', priority: 1,
    whyItMatters: 'A greenfield project means designing all systems from scratch — perfect timing to introduce best-practice PLM and digital thread before legacy habits form.',
    pitchAngle: 'Establish a Digital Thread from day one — PLM, CAD, NPD workflows, and ERP integration before production starts.',
    keyServices: 'PLM Implementation, Digital Thread Enablement, NPD Support, CAD/CAM Setup, ERP Integration',
    talkTrack1: 'When you\'re building from the ground up, the biggest risk is siloed systems. We help manufacturers establish a connected digital backbone — PLM to ERP to shop floor — so every change is tracked from design to delivery.',
    talkTrack2: 'We\'ve helped greenfield plants in automotive and industrial sectors reduce engineering rework by 40% by implementing PLM before production starts. What\'s your current plan for managing design data?',
    talkTrack3: 'SolidPro can deploy a full PLM + CAD + ERP integration stack within your go-live timeline. We handle data migration, user training, and post-go-live support.',
  },
  {
    signal: 'ERP Implementation', category: 'Technology Adoption', priority: 1,
    whyItMatters: 'ERP rollouts create massive demand for CAD/PLM integration, engineering data migration, and CPQ alignment.',
    pitchAngle: 'Connect your ERP to your engineering data — CAD integration, BOM sync, CPQ, and engineering change management.',
    keyServices: 'ERP Integration (SAP/Epicor/Dynamics), CAD Integration, Engineering Data Migration, CPQ Implementation',
    talkTrack1: 'Most ERP rollouts stall because engineering data — BOMs, drawings, change orders — isn\'t clean or connected. We specialize in bridging ERP and PLM so your production data is always current.',
    talkTrack2: 'Are you also migrating your CAD and product data into the new ERP? That\'s typically where projects hit delays. We can handle the engineering side of your data migration.',
    talkTrack3: 'SolidPro has delivered ERP-CAD integrations for over 50 manufacturers. We work alongside your ERP SI to ensure the engineering data layer is solid.',
  },
  {
    signal: 'SAP Implementation', category: 'Technology Adoption', priority: 1,
    whyItMatters: 'SAP S/4HANA rollouts specifically need Industrial CPQ, PLM integration, and precise engineering data migration.',
    pitchAngle: 'SAP S/4HANA integration with PLM, Industrial CPQ, and engineering data migration.',
    keyServices: 'SAP S/4HANA Integration, Industrial CPQ, PLM-SAP Bridge, Engineering Data Migration',
    talkTrack1: 'SAP S/4HANA implementations frequently underestimate the engineering data side — BOMs, material masters, and drawing management. We fill that gap.',
    talkTrack2: 'We\'ve integrated Windchill, Teamcenter, and Vault with SAP for manufacturers globally. Are you looking at PLM integration as part of your S/4HANA roadmap?',
    talkTrack3: 'Our Industrial CPQ practice specifically bridges SAP with configure-price-quote workflows for engineered-to-order manufacturers — cutting quote cycle times by 60%.',
  },
  {
    signal: 'Digital Transformation', category: 'Technology Adoption', priority: 2,
    whyItMatters: 'Digital transformation programs need a trusted engineering technology partner for CPQ, PLM, workflow automation, and AI-enabled engineering.',
    pitchAngle: 'End-to-end digital thread: CPQ → PLM → ERP → shop floor, with AI-enabled engineering workflows.',
    keyServices: 'CPQ, PLM, Digital Thread, AI-enabled Engineering, Workflow Automation',
    talkTrack1: 'Digital transformation in manufacturing means connecting commercial, engineering, and production data. We build the digital thread that makes this possible.',
    talkTrack2: 'Where are you starting — commercial (CPQ), engineering (PLM), or production (MES)? We can help at any layer and ensure they connect.',
    talkTrack3: 'SolidPro\'s digital transformation engagements typically deliver ROI within 12 months through reduced rework, faster quoting, and eliminated manual data entry.',
  },
  {
    signal: 'Factory Automation', category: 'Technology Adoption', priority: 1,
    whyItMatters: 'Automation projects require special purpose machines, embedded software, SCADA/MES integration, and technical documentation.',
    pitchAngle: 'Special Purpose Machines, embedded software, SCADA/MES integration, and AR/VR operator work instructions.',
    keyServices: 'Special Purpose Machines & Automation, Embedded Software, SCADA/MES Integration, AR/VR Work Instructions, Operator Manuals',
    talkTrack1: 'Factory automation without good documentation leads to expensive downtime. We deliver the automation machinery AND the digital work instructions your operators need.',
    talkTrack2: 'Are you automating a specific line or the whole plant? We build custom automation systems and the embedded software that runs them.',
    talkTrack3: 'SolidPro\'s AR/VR work instructions have reduced training time by 50% at automotive tier-1 suppliers. Your operators learn faster and make fewer errors.',
  },
  {
    signal: 'Smart Factory', category: 'Technology Adoption', priority: 1,
    whyItMatters: 'Smart factory programs need IIoT integration, AI-enabled engineering, and a complete digital thread.',
    pitchAngle: 'IIoT integration, Digital Thread, AI-enabled quality and maintenance, connected work instructions.',
    keyServices: 'IIoT Integration, Digital Thread Enablement, IC Remote Monitoring, AI-enabled Engineering, AR/VR Work Instructions',
    talkTrack1: 'Smart factories need more than sensors — they need the data infrastructure to act on what those sensors report. We build the digital thread that connects machine data to engineering decisions.',
    talkTrack2: 'Our IC Remote Monitoring platform gives you real-time visibility into machine performance and predictive maintenance — without ripping out existing equipment.',
    talkTrack3: 'We\'ve deployed smart factory solutions across automotive, aerospace, and industrial sectors. What\'s your primary goal — OEE improvement, quality, or predictive maintenance?',
  },
  {
    signal: 'Cloud Migration', category: 'Technology Adoption', priority: 2,
    whyItMatters: 'Cloud migrations for manufacturers mean PLM migration, ERP cloud integration, and engineering data governance.',
    pitchAngle: 'PLM cloud migration, ERP cloud integration, engineering data governance and security.',
    keyServices: 'Cloud Transformation, PLM Migration, ERP Integration, Engineering Data Migration',
    talkTrack1: 'Cloud PLM migrations are complex — you\'re not just moving files, you\'re moving your entire product knowledge base. We ensure zero data loss and full traceability.',
    talkTrack2: 'Are you moving to cloud PLM (Windchill+, 3DEXPERIENCE) or cloud ERP (S/4HANA Cloud, Dynamics 365)? We handle both and the integration between them.',
    talkTrack3: 'SolidPro has migrated over 10TB of engineering data to cloud PLM platforms with no production disruption. What\'s your migration timeline?',
  },
  {
    signal: 'IoT Adoption', category: 'Technology Adoption', priority: 2,
    whyItMatters: 'IoT adoption in manufacturing creates demand for IC remote monitoring, embedded firmware, and hardware design.',
    pitchAngle: 'IC Remote Monitoring platform, embedded firmware, hardware design, and IoT data integration.',
    keyServices: 'IC Remote Monitoring, Embedded Software/Firmware, Hardware Design, IoT Data Integration',
    talkTrack1: 'IoT without actionable insights is just expensive sensors. Our IC Remote Monitoring platform turns machine data into maintenance alerts, OEE dashboards, and quality signals.',
    talkTrack2: 'Are you connecting existing equipment or specifying new connected machines? We handle both — from firmware development to cloud connectivity.',
    talkTrack3: 'We\'ve deployed IoT solutions in discrete manufacturing, process industries, and utilities. Our firmware team can work with any hardware platform.',
  },
  {
    signal: 'Manufacturing Hiring', category: 'Hiring & Talent', priority: 2,
    whyItMatters: 'Hiring for manufacturing roles signals growth; hiring for PLM/CAD/automation roles signals a technology project underway.',
    pitchAngle: 'Augment your engineering capacity — CAD/PLM contract engineers, technical publications, AR/VR training.',
    keyServices: 'Engineering Staffing, AR/VR Work Instructions, Technical Publications, Operator Training',
    talkTrack1: 'Hiring engineers is a long-cycle activity. For immediate capacity, SolidPro provides experienced CAD, PLM, and automation engineers on demand.',
    talkTrack2: 'If you\'re hiring for PLM or automation, you likely have a project underway that needs experienced hands. What\'s the project scope?',
    talkTrack3: 'We\'ve helped manufacturers bridge engineering capacity gaps during plant ramp-ups, product launches, and system migrations — without the overhead of permanent headcount.',
  },
  {
    signal: 'Technology Hiring', category: 'Hiring & Talent', priority: 1,
    whyItMatters: 'Hiring SAP, CPQ, PLM, or automation engineers is a direct signal of an active technology project.',
    pitchAngle: 'Project partnership — SolidPro delivers the same outcome with experienced teams, faster and at lower risk than building in-house.',
    keyServices: 'SAP Integration, CPQ Implementation, PLM Deployment, Engineering Staff Augmentation',
    talkTrack1: 'You\'re hiring for SAP/CPQ/PLM — that tells me you have a project that needs expertise you don\'t currently have. SolidPro can deliver the same outcome as a project partner.',
    talkTrack2: 'Hiring and onboarding takes 3-6 months. We can have an experienced team on your project in 2 weeks. What\'s your go-live pressure?',
    talkTrack3: 'Our advantage is experience across dozens of similar implementations. We bring proven methodology, not just headcount.',
  },
  {
    signal: 'Acquisition', category: 'M&A Activity', priority: 2,
    whyItMatters: 'Acquisitions create immediate need for PLM data migration, ERP consolidation, and engineering data standardization.',
    pitchAngle: 'Post-acquisition PLM migration, ERP integration, and engineering data standardization across entities.',
    keyServices: 'PLM Migration, Engineering Data Migration, ERP Integration, MDM',
    talkTrack1: 'Post-acquisition, the hardest integration problem is engineering data — two companies with different CAD tools, PLM systems, and BOMs. We specialize in this.',
    talkTrack2: 'How are you planning to consolidate the engineering and product data from the acquired entity? That\'s typically the most expensive and time-consuming part.',
    talkTrack3: 'SolidPro has managed PLM migrations during M&A for automotive, aerospace, and industrial manufacturers. We have a proven playbook.',
  },
  {
    signal: 'Joint Venture', category: 'M&A Activity', priority: 2,
    whyItMatters: 'Joint ventures need shared data infrastructure — PLM collaboration, ERP integration, and engineering change management.',
    pitchAngle: 'Shared PLM environment, ERP integration, and engineering collaboration workflows for JV entities.',
    keyServices: 'PLM Collaboration Setup, ERP Integration, Engineering Change Management, MDM',
    talkTrack1: 'Joint ventures require two organizations to share engineering data securely. We set up the PLM collaboration infrastructure that makes this possible.',
    talkTrack2: 'Are both entities on the same CAD/PLM platform? If not, we handle multi-platform integration and data translation.',
    talkTrack3: 'We\'ve enabled PLM collaboration for JVs across geographies — US-India, EU-US, and India-Japan setups. We understand the data governance challenges.',
  },
  {
    signal: 'New Contract', category: 'Market Activity', priority: 2,
    whyItMatters: 'A new major contract often means ramping up engineering and production capacity quickly.',
    pitchAngle: 'Rapid engineering capacity augmentation — CAD, PLM, technical publications for contract delivery.',
    keyServices: 'Engineering Staff Augmentation, CAD/CAM Services, Technical Publications, NPD Support',
    talkTrack1: 'A new contract win often means immediate pressure on engineering bandwidth. We can augment your team with experienced CAD and PLM engineers within days.',
    talkTrack2: 'Does this contract require new product development or adapting existing designs? We support both paths.',
    talkTrack3: 'SolidPro has supported contract delivery for aerospace, defense, and automotive manufacturers — from design to technical documentation.',
  },
  {
    signal: 'Quality Certification', category: 'Regulatory & Compliance', priority: 2,
    whyItMatters: 'Quality certifications (ISO, AS9100, IATF) require documented processes, controlled documents, and technical publications.',
    pitchAngle: 'S1000D authoring, DITA publishing, DFMEA/PFMEA, controlled technical documentation for certification.',
    keyServices: 'S1000D Authoring, DITA Publishing, DFMEA/PFMEA, Technical Publications, Document Control',
    talkTrack1: 'Quality certifications demand structured, controlled technical documentation. We build the document framework that satisfies ISO, AS9100, and IATF auditors.',
    talkTrack2: 'Are you also updating your DFMEA/PFMEA as part of this certification? We provide analysis and documentation together.',
    talkTrack3: 'SolidPro\'s technical publications team has supported certification audits across aerospace, automotive, and industrial sectors.',
  },
  {
    signal: 'Environmental Compliance', category: 'Regulatory & Compliance', priority: 2,
    whyItMatters: 'Environmental compliance and ESG commitments create demand for sustainability platforms, value engineering, and compliance documentation.',
    pitchAngle: 'NetZeroHub sustainability platform, value engineering for material reduction, ESG documentation.',
    keyServices: 'NetZeroHub Platform, Sustainability Engineering, Value Engineering, ESG Reporting, DFM/DFA',
    talkTrack1: 'Environmental compliance isn\'t just reporting — it\'s engineering. We help manufacturers redesign products for reduced material use and lower emissions through value engineering.',
    talkTrack2: 'Are you looking for a platform to track and report ESG metrics, or do you need engineering support to actually reduce your environmental footprint?',
    talkTrack3: 'SolidPro\'s NetZeroHub platform integrates with your ERP and PLM to provide real-time sustainability dashboards and automated ESG reporting.',
  },
  {
    signal: 'Supply Chain Change', category: 'Supply Chain & Logistics', priority: 2,
    whyItMatters: 'Supply chain restructuring creates demand for ERP integration, CPQ reconfiguration, and procurement automation.',
    pitchAngle: 'ERP-CPQ integration for supplier data, procurement automation, engineering BOM alignment.',
    keyServices: 'ERP Integration, CPQ Implementation, Industrial CPQ, Procurement Automation',
    talkTrack1: 'Supply chain changes often break existing ERP and CPQ configurations. We ensure your systems reflect your new supplier relationships correctly.',
    talkTrack2: 'Are you adding new suppliers, changing tier structure, or reshoring? Each creates different data integration challenges.',
    talkTrack3: 'We\'ve helped manufacturers reconfigure their CPQ and ERP systems after major supply chain restructuring — reducing quote errors and procurement cycle time.',
  },
];

export async function seedPitchPlaybook(): Promise<number> {
  let count = 0;
  for (const entry of PLAYBOOK) {
    const existing = await db.select().from(pitchPlaybook)
      .where(eq(pitchPlaybook.signal, entry.signal)).get();
    if (!existing) {
      await db.insert(pitchPlaybook).values(entry).run();
      count++;
    }
  }
  return count;
}
