-- =========================================================================
-- CRX CRM — Comprehensive Seed Data
-- 100% Native SQLite / MySQL compatible seed
-- =========================================================================

-- 1. Roles
INSERT INTO roles (id, name, slug) VALUES 
(1, 'Administrator', 'admin'),
(2, 'Member', 'member'),
(3, 'Guest', 'guest');

-- 2. Permissions
INSERT INTO permissions (id, name, slug) VALUES 
(1, 'Manage Workspace', 'manage_workspace'),
(2, 'Manage Users', 'manage_users'),
(3, 'Manage Deals', 'manage_deals'),
(4, 'Manage Contacts', 'manage_contacts'),
(5, 'Manage Custom Fields', 'manage_custom_fields'),
(6, 'Access MCP Server', 'access_mcp');

-- 3. Role Permissions
INSERT INTO role_permissions (role_id, permission_id) VALUES 
(1, 1), (1, 2), (1, 3), (1, 4), (1, 5), (1, 6),
(2, 3), (2, 4), (2, 6),
(3, 6);

-- 4. Users (Password for all users: 'password123')
INSERT INTO users (id, name, email, password, avatar, created_at, updated_at) VALUES 
(1, 'Alex Mercer', 'admin@crx.local', '$2y$10$Tfbb3yC6Fl82ATU.JIXFpuYV/rmWBzDtqWW22P5zc6UchbHDyN0Ra', NULL, datetime('now'), datetime('now')),
(2, 'Sarah Jenkins', 'sarah.jenkins@crx.local', '$2y$10$Tfbb3yC6Fl82ATU.JIXFpuYV/rmWBzDtqWW22P5zc6UchbHDyN0Ra', NULL, datetime('now'), datetime('now')),
(3, 'Marcus Brody', 'marcus.brody@crx.local', '$2y$10$Tfbb3yC6Fl82ATU.JIXFpuYV/rmWBzDtqWW22P5zc6UchbHDyN0Ra', NULL, datetime('now'), datetime('now')),
(4, 'Elena Rostova', 'elena.rostova@crx.local', '$2y$10$Tfbb3yC6Fl82ATU.JIXFpuYV/rmWBzDtqWW22P5zc6UchbHDyN0Ra', NULL, datetime('now'), datetime('now')),
(5, 'CRX Autonomous Agent', 'agent@crx.local', '$2y$10$Tfbb3yC6Fl82ATU.JIXFpuYV/rmWBzDtqWW22P5zc6UchbHDyN0Ra', NULL, datetime('now'), datetime('now'));

-- 5. User Roles
INSERT INTO user_roles (user_id, role_id) VALUES 
(1, 1),
(2, 2),
(3, 2),
(4, 2),
(5, 1);

-- 6. Workspaces
INSERT INTO workspaces (id, name, slug, owner_id, settings, created_at, updated_at) VALUES 
(1, 'Acme Global Ventures', 'acme-global', 1, '{"currency":"USD","timezone":"America/New_York"}', datetime('now'), datetime('now')),
(2, 'NovaCore AI Labs', 'novacore-ai', 1, '{"currency":"EUR","timezone":"Europe/London"}', datetime('now'), datetime('now'));

-- 7. Memberships
INSERT INTO memberships (id, workspace_id, user_id, role, created_at, updated_at) VALUES 
(1, 1, 1, 'admin', datetime('now'), datetime('now')),
(2, 1, 2, 'member', datetime('now'), datetime('now')),
(3, 1, 3, 'member', datetime('now'), datetime('now')),
(4, 1, 4, 'member', datetime('now'), datetime('now')),
(5, 1, 5, 'admin', datetime('now'), datetime('now')),
(6, 2, 1, 'admin', datetime('now'), datetime('now')),
(7, 2, 2, 'member', datetime('now'), datetime('now'));

-- 8. Companies (15 Diverse Accounts)
INSERT INTO companies (id, workspace_id, name, domain, industry, size, phone, email, website, address, city, country, annual_revenue, description, assigned_user_id, created_at, updated_at) VALUES 
(1, 1, 'Stripe Financial', 'stripe.com', 'FinTech', '1000+', '+1-415-555-0101', 'sales@stripe.com', 'https://stripe.com', '354 Oyster Point Blvd', 'South San Francisco', 'USA', 145000000.00, 'Global financial infrastructure and payment processing platform.', 1, datetime('now'), datetime('now')),
(2, 1, 'Supabase Inc', 'supabase.com', 'Developer Tools', '100-500', '+1-415-555-0102', 'contact@supabase.com', 'https://supabase.com', '970 Folsom St', 'San Francisco', 'USA', 24000000.00, 'Open source Firebase alternative providing Postgres, Auth, and Edge Functions.', 2, datetime('now'), datetime('now')),
(3, 1, 'Linear Orbit', 'linear.app', 'SaaS', '50-100', '+1-415-555-0103', 'hello@linear.app', 'https://linear.app', '548 Market St', 'San Francisco', 'USA', 18000000.00, 'Issue tracking and project management tool built for high-velocity teams.', 3, datetime('now'), datetime('now')),
(4, 1, 'Vercel Cloud', 'vercel.com', 'Cloud Computing', '500-1000', '+1-415-555-0104', 'enterprise@vercel.com', 'https://vercel.com', '340 S Lemon Ave', 'Walnut', 'USA', 65000000.00, 'Frontend cloud architecture and serverless edge delivery.', 1, datetime('now'), datetime('now')),
(5, 1, 'Datadog Observability', 'datadoghq.com', 'DevOps & Monitoring', '1000+', '+1-866-328-2364', 'contact@datadoghq.com', 'https://datadoghq.com', '620 8th Ave 45th floor', 'New York', 'USA', 210000000.00, 'Cloud-scale monitoring and security platform for metrics, traces, and logs.', 2, datetime('now'), datetime('now')),
(6, 1, 'Figma Creative', 'figma.com', 'Design Software', '1000+', '+1-415-555-0106', 'sales@figma.com', 'https://figma.com', '760 Market St', 'San Francisco', 'USA', 90000000.00, 'Collaborative web-first interface design and prototyping tool.', 3, datetime('now'), datetime('now')),
(7, 1, 'Snowflake Data Cloud', 'snowflake.com', 'Big Data', '1000+', '+1-844-766-9355', 'sales@snowflake.com', 'https://snowflake.com', '106 East Babcock St', 'Bozeman', 'USA', 350000000.00, 'Cloud data platform powering data warehouses, data lakes, and AI apps.', 1, datetime('now'), datetime('now')),
(8, 1, 'Notion Labs', 'notion.so', 'Productivity', '500-1000', '+1-415-555-0108', 'team@makenotion.com', 'https://notion.so', '2300 Harrison St', 'San Francisco', 'USA', 42000000.00, 'Connected workspace for wiki, docs, and project management.', 2, datetime('now'), datetime('now')),
(9, 1, 'Retool Enterprise', 'retool.com', 'Enterprise Dev', '100-500', '+1-415-555-0109', 'sales@retool.com', 'https://retool.com', '2131 19th St', 'San Francisco', 'USA', 31000000.00, 'The fast way to build internal software, admin panels, and agent tools.', 3, datetime('now'), datetime('now')),
(10, 1, 'Anthropic Research', 'anthropic.com', 'Artificial Intelligence', '500-1000', '+1-415-555-0110', 'sales@anthropic.com', 'https://anthropic.com', '500 Howard St', 'San Francisco', 'USA', 120000000.00, 'AI safety and research company building reliable, interpretable Claude systems.', 1, datetime('now'), datetime('now')),
(11, 1, 'Mistral AI', 'mistral.ai', 'Artificial Intelligence', '50-100', '+33-1-55-55-01', 'contact@mistral.ai', 'https://mistral.ai', '15 Rue de Turbigo', 'Paris', 'France', 28000000.00, 'Frontier AI models and open weights generative intelligence platform.', 2, datetime('now'), datetime('now')),
(12, 1, 'Cloudflare Network', 'cloudflare.com', 'Cybersecurity', '1000+', '+1-888-993-5273', 'enterprise@cloudflare.com', 'https://cloudflare.com', '101 Townsend St', 'San Francisco', 'USA', 185000000.00, 'Global security, performance, and reliability edge network.', 3, datetime('now'), datetime('now')),
(13, 1, 'Shopify Commerce', 'shopify.com', 'E-Commerce', '1000+', '+1-888-746-7439', 'enterprise@shopify.com', 'https://shopify.com', '151 O''Connor St', 'Ottawa', 'Canada', 480000000.00, 'Omnichannel commerce platform powering millions of global online businesses.', 1, datetime('now'), datetime('now')),
(14, 1, 'Canva Design', 'canva.com', 'Graphic Design', '1000+', '+61-2-5550-0114', 'sales@canva.com', 'https://canva.com', '110 Kippax St', 'Sydney', 'Australia', 110000000.00, 'Visual communication platform empowering anyone in the world to design.', 2, datetime('now'), datetime('now')),
(15, 1, 'Monzo Bank', 'monzo.com', 'Digital Banking', '1000+', '+44-20-3872-0620', 'business@monzo.com', 'https://monzo.com', 'Broadwalk House 5 Appold St', 'London', 'UK', 75000000.00, 'Next-generation mobile bank with real-time financial tracking and smart cards.', 3, datetime('now'), datetime('now'));

-- 9. People / Contacts (25+ Detailed Contacts)
INSERT INTO people (id, workspace_id, company_id, first_name, last_name, email, phone, job_title, status, assigned_user_id, created_at, updated_at) VALUES 
(1, 1, 1, 'Patrick', 'Collison', 'patrick@stripe.com', '+1-415-555-2341', 'Chief Executive Officer', 'customer', 1, datetime('now'), datetime('now')),
(2, 1, 1, 'Claire', 'Hughes', 'claire.h@stripe.com', '+1-415-555-2342', 'VP of Global Operations', 'customer', 2, datetime('now'), datetime('now')),
(3, 1, 2, 'Paul', 'Copplestone', 'paul@supabase.com', '+1-415-555-4892', 'CEO & Co-Founder', 'customer', 2, datetime('now'), datetime('now')),
(4, 1, 2, 'Ant', 'Wilson', 'ant@supabase.com', '+1-415-555-4893', 'CTO & Co-Founder', 'customer', 4, datetime('now'), datetime('now')),
(5, 1, 3, 'Karri', 'Saarinen', 'karri@linear.app', '+1-415-555-9831', 'Head of Product & Design', 'customer', 3, datetime('now'), datetime('now')),
(6, 1, 3, 'Tuomas', 'Artman', 'tuomas@linear.app', '+1-415-555-9832', 'Co-Founder & Chief Architect', 'customer', 3, datetime('now'), datetime('now')),
(7, 1, 4, 'Guillermo', 'Rauch', 'guillermo@vercel.com', '+1-415-555-1290', 'CEO & Founder', 'customer', 1, datetime('now'), datetime('now')),
(8, 1, 4, 'Malte', 'Ubl', 'malte@vercel.com', '+1-415-555-1291', 'VP of Engineering', 'customer', 4, datetime('now'), datetime('now')),
(9, 1, 5, 'Olivier', 'Pomel', 'olivier@datadoghq.com', '+1-212-555-0150', 'Chief Executive Officer', 'customer', 2, datetime('now'), datetime('now')),
(10, 1, 5, 'Alexis', 'L-Cquoq', 'alexis@datadoghq.com', '+1-212-555-0151', 'Chief Technology Officer', 'prospect', 2, datetime('now'), datetime('now')),
(11, 1, 6, 'Dylan', 'Field', 'dylan@figma.com', '+1-415-555-0160', 'CEO & Co-Founder', 'customer', 3, datetime('now'), datetime('now')),
(12, 1, 6, 'Sho', 'Kuwamoto', 'sho@figma.com', '+1-415-555-0161', 'VP of Product', 'prospect', 3, datetime('now'), datetime('now')),
(13, 1, 7, 'Frank', 'Slootman', 'frank@snowflake.com', '+1-406-555-0170', 'Chairman & Executive', 'lead', 1, datetime('now'), datetime('now')),
(14, 1, 7, 'Benoit', 'Dageville', 'benoit@snowflake.com', '+1-406-555-0171', 'President of Products', 'lead', 4, datetime('now'), datetime('now')),
(15, 1, 8, 'Ivan', 'Zhao', 'ivan@notion.so', '+1-415-555-0180', 'CEO & Co-Founder', 'customer', 2, datetime('now'), datetime('now')),
(16, 1, 8, 'Akshay', 'Kothari', 'akshay@notion.so', '+1-415-555-0181', 'Co-Founder & COO', 'customer', 2, datetime('now'), datetime('now')),
(17, 1, 9, 'David', 'Hsu', 'david@retool.com', '+1-415-555-0190', 'Founder & CEO', 'customer', 3, datetime('now'), datetime('now')),
(18, 1, 10, 'Dario', 'Amodei', 'dario@anthropic.com', '+1-415-555-0200', 'Chief Executive Officer', 'customer', 1, datetime('now'), datetime('now')),
(19, 1, 10, 'Daniela', 'Amodei', 'daniela@anthropic.com', '+1-415-555-0201', 'President', 'customer', 1, datetime('now'), datetime('now')),
(20, 1, 11, 'Arthur', 'Mensch', 'arthur@mistral.ai', '+33-1-55-55-0210', 'Chief Executive Officer', 'lead', 2, datetime('now'), datetime('now')),
(21, 1, 12, 'Matthew', 'Prince', 'matthew@cloudflare.com', '+1-415-555-0220', 'Co-Founder & CEO', 'customer', 3, datetime('now'), datetime('now')),
(22, 1, 12, 'Michelle', 'Zatlyn', 'michelle@cloudflare.com', '+1-415-555-0221', 'President & COO', 'customer', 3, datetime('now'), datetime('now')),
(23, 1, 13, 'Harley', 'Finkelstein', 'harley@shopify.com', '+1-613-555-0230', 'President', 'lead', 1, datetime('now'), datetime('now')),
(24, 1, 14, 'Melanie', 'Perkins', 'melanie@canva.com', '+61-2-5550-0240', 'CEO & Co-Founder', 'prospect', 2, datetime('now'), datetime('now')),
(25, 1, 15, 'TS', 'Anil', 'ts.anil@monzo.com', '+44-20-3872-0250', 'Chief Executive Officer', 'customer', 3, datetime('now'), datetime('now'));

-- 10. Opportunities / Pipeline (22 Deals across all 6 stages)
INSERT INTO opportunities (id, workspace_id, company_id, person_id, name, amount, currency, stage, probability, expected_close_date, status, assigned_user_id, created_at, updated_at) VALUES 
-- Stage: Lead (4)
(1, 1, 7, 13, 'Data Warehouse AI Integration', 185000.00, 'USD', 'lead', 20, date('now', '+60 days'), 'open', 1, datetime('now'), datetime('now')),
(2, 1, 11, 20, 'Open Weights LLM Fine-Tuning Cluster', 64000.00, 'USD', 'lead', 25, date('now', '+75 days'), 'open', 2, datetime('now'), datetime('now')),
(3, 1, 13, 23, 'Merchant Checkout Headless Extension', 42000.00, 'USD', 'lead', 20, date('now', '+90 days'), 'open', 1, datetime('now'), datetime('now')),
(4, 1, 14, 24, 'Creative Asset Management API', 55000.00, 'USD', 'lead', 30, date('now', '+45 days'), 'open', 2, datetime('now'), datetime('now')),

-- Stage: Qualified (4)
(5, 1, 3, 5, 'Sprint Tracking Automation Connector', 38000.00, 'USD', 'qualified', 40, date('now', '+40 days'), 'open', 3, datetime('now'), datetime('now')),
(6, 1, 5, 10, 'Distributed Tracing Telemetry Pipeline', 82000.00, 'USD', 'qualified', 45, date('now', '+35 days'), 'open', 2, datetime('now'), datetime('now')),
(7, 1, 8, 16, 'Workspace Knowledge Graph Sync', 49000.00, 'USD', 'qualified', 50, date('now', '+50 days'), 'open', 2, datetime('now'), datetime('now')),
(8, 1, 9, 17, 'Low-Code Agent Action Workflows', 76000.00, 'USD', 'qualified', 45, date('now', '+30 days'), 'open', 3, datetime('now'), datetime('now')),

-- Stage: Proposal (4)
(9, 1, 1, 2, 'Multi-Currency Settlement Gateway', 145000.00, 'USD', 'proposal', 60, date('now', '+25 days'), 'open', 1, datetime('now'), datetime('now')),
(10, 1, 4, 8, 'Edge Middleware Security Audit', 92000.00, 'USD', 'proposal', 65, date('now', '+20 days'), 'open', 4, datetime('now'), datetime('now')),
(11, 1, 6, 12, 'Design System Token Exporter Pro', 58000.00, 'USD', 'proposal', 60, date('now', '+30 days'), 'open', 3, datetime('now'), datetime('now')),
(12, 1, 12, 22, 'Workers Zero-Trust WAF Enterprise Rulepack', 115000.00, 'USD', 'proposal', 70, date('now', '+15 days'), 'open', 3, datetime('now'), datetime('now')),

-- Stage: Negotiation (4)
(13, 1, 2, 3, 'Postgres Enterprise Read Replica Licensing', 88000.00, 'USD', 'negotiation', 80, date('now', '+10 days'), 'open', 2, datetime('now'), datetime('now')),
(14, 1, 10, 19, 'Claude 3.5 Sonnet High-Throughput Tier', 240000.00, 'USD', 'negotiation', 85, date('now', '+7 days'), 'open', 1, datetime('now'), datetime('now')),
(15, 1, 15, 25, 'Digital Banking Fraud Shield Engine', 130000.00, 'USD', 'negotiation', 80, date('now', '+12 days'), 'open', 3, datetime('now'), datetime('now')),
(16, 1, 5, 9, 'Log Retention & Compliance Tier Extension', 72000.00, 'USD', 'negotiation', 75, date('now', '+14 days'), 'open', 2, datetime('now'), datetime('now')),

-- Stage: Closed Won (4)
(17, 1, 1, 1, 'Global Merchant API Integration', 125000.00, 'USD', 'closed_won', 100, date('now', '-10 days'), 'won', 1, datetime('now'), datetime('now')),
(18, 1, 4, 7, 'Next.js Edge Deployment License 2026', 160000.00, 'USD', 'closed_won', 100, date('now', '-20 days'), 'won', 1, datetime('now'), datetime('now')),
(19, 1, 2, 4, 'Supabase Realtime Cluster Agreement', 52000.00, 'USD', 'closed_won', 100, date('now', '-15 days'), 'won', 2, datetime('now'), datetime('now')),
(20, 1, 6, 11, 'Enterprise Design Collaboration Package', 98000.00, 'USD', 'closed_won', 100, date('now', '-5 days'), 'won', 3, datetime('now'), datetime('now')),

-- Stage: Closed Lost (2)
(21, 1, 8, 15, 'Legacy Wiki Import Migration Service', 34000.00, 'USD', 'closed_lost', 0, date('now', '-30 days'), 'lost', 2, datetime('now'), datetime('now')),
(22, 1, 12, 21, 'Custom DNS Routing Appliances', 45000.00, 'USD', 'closed_lost', 0, date('now', '-40 days'), 'lost', 3, datetime('now'), datetime('now'));

-- 11. Tasks (18 Detailed Tasks)
INSERT INTO tasks (id, workspace_id, title, description, due_date, priority, status, entity_type, entity_id, assigned_user_id, created_at, updated_at) VALUES 
(1, 1, 'Finalize Merchant Agreement SLA Draft', 'Coordinate with legal team regarding cross-border settlement clauses.', date('now', '+2 days'), 'urgent', 'pending', 'opportunities', 9, 1, datetime('now'), datetime('now')),
(2, 1, 'Schedule High-Throughput Token Capacity Review', 'Review token rate limits and dedicated inference hardware with Daniela.', date('now', '+1 days'), 'urgent', 'in_progress', 'opportunities', 14, 1, datetime('now'), datetime('now')),
(3, 1, 'Prepare Security Architecture Whitepaper', 'Provide SOC2 Type II compliance audit packet to Alexis at Datadog.', date('now', '+4 days'), 'high', 'pending', 'companies', 5, 2, datetime('now'), datetime('now')),
(4, 1, 'Send Technical Benchmark Report to Paul', 'Include Postgres 17 connection pooling and PgBouncer performance specs.', date('now', '+3 days'), 'high', 'in_progress', 'people', 3, 2, datetime('now'), datetime('now')),
(5, 1, 'Executive Dinner at SaaStr Conference', 'Confirm table booking for Patrick Collison and Alex Mercer.', date('now', '+7 days'), 'medium', 'pending', 'people', 1, 1, datetime('now'), datetime('now')),
(6, 1, 'Draft Figma Plugin Architecture RFC', 'Document bi-directional webhook updates for component tokens.', date('now', '+5 days'), 'medium', 'pending', 'opportunities', 11, 3, datetime('now'), datetime('now')),
(7, 1, 'Verify Fraud Prevention SLA Metrics', 'Review latency thresholds (<15ms) for UK transaction routing with Monzo.', date('now', '+6 days'), 'high', 'pending', 'opportunities', 15, 3, datetime('now'), datetime('now')),
(8, 1, 'Customer Onboarding Kickoff Call with Vercel', 'Align engineering teams on Next.js 16 deployment canary workflows.', date('now', '-2 days'), 'medium', 'completed', 'opportunities', 18, 4, datetime('now'), datetime('now')),
(9, 1, 'Collect Signed Master Services Agreement', 'Stripe legal team has executed the final DPA. Archive copy in drive.', date('now', '-5 days'), 'low', 'completed', 'opportunities', 17, 1, datetime('now'), datetime('now')),
(10, 1, 'Review Quarterly Retention Metrics with Supabase', 'Review seat utilization and API query growth trends for Q3.', date('now', '+10 days'), 'low', 'pending', 'companies', 2, 2, datetime('now'), datetime('now')),
(11, 1, 'Follow Up with Arthur on EU AI Act Compliance', 'Ensure Mistral deployment topology adheres to French data sovereignty.', date('now', '+8 days'), 'high', 'pending', 'people', 20, 2, datetime('now'), datetime('now')),
(12, 1, 'Run Agent Autonomous Lead Enrichment', 'Execute CRX MCP enrich_company tool across unassigned prospect rows.', date('now', '0 days'), 'urgent', 'pending', 'companies', 13, 5, datetime('now'), datetime('now')),
(13, 1, 'Send Linear Integration Demo Video', 'Record 3-minute walkthrough demonstrating bidirectional issue sync.', date('now', '+4 days'), 'medium', 'pending', 'opportunities', 5, 3, datetime('now'), datetime('now')),
(14, 1, 'Set Up Retool Custom Component Sandbox', 'Configure OAuth client credentials for the Retool engineering eval.', date('now', '+3 days'), 'medium', 'in_progress', 'companies', 9, 3, datetime('now'), datetime('now')),
(15, 1, 'Review Cloudflare Zero-Trust Routing Specs', 'Examine mTLS certificate pinning details for edge API gateway.', date('now', '+9 days'), 'high', 'pending', 'opportunities', 12, 3, datetime('now'), datetime('now')),
(16, 1, 'Sync with Dylan on Collaborative Canvas Limits', 'Discuss enterprise multiplayer presence architecture over WebSockets.', date('now', '-1 days'), 'low', 'completed', 'people', 11, 3, datetime('now'), datetime('now')),
(17, 1, 'Coordinate Snowflake Snowpark Python Demo', 'Demonstrate native Spartan SQL generation inside Snowflake notebook.', date('now', '+12 days'), 'medium', 'pending', 'companies', 7, 4, datetime('now'), datetime('now')),
(18, 1, 'Send Welcome Gift Box to Canva Executive Team', 'Send curated team gifts to Sydney HQ celebrating the new partnership.', date('now', '+14 days'), 'low', 'pending', 'companies', 14, 2, datetime('now'), datetime('now'));

-- 12. Notes (18 Comprehensive Meeting & Account Notes)
INSERT INTO notes (id, workspace_id, entity_type, entity_id, user_id, title, body, created_at, updated_at) VALUES 
(1, 1, 'companies', 1, 1, 'Executive Dinner Notes with Patrick', 'Patrick is very receptive to CRX agent-native approach. Key focus is frictionless API settlement and automatic invoice recon.', datetime('now', '-2 days'), datetime('now', '-2 days')),
(2, 1, 'opportunities', 14, 1, 'Anthropic Pricing & Capacity Deep Dive', 'Discussed 100M token/day commitment. Dario and Daniela emphasized safety guardrails and deterministic latency targets. Proposal adjusted to $240k.', datetime('now', '-1 days'), datetime('now', '-1 days')),
(3, 1, 'companies', 2, 2, 'Supabase Technical Architecture Sync', 'Paul and Ant confirmed that CRX Spartan query builder dialect generates clean SQLite and PostgreSQL queries with zero runtime overhead.', datetime('now', '-3 days'), datetime('now', '-3 days')),
(4, 1, 'opportunities', 13, 2, 'Replica Licensing Terms Discussion', 'Negotiation is centering on multi-region failover. We offered 99.99% uptime guarantee with dedicated support channel in Slack.', datetime('now', '-2 days'), datetime('now', '-2 days')),
(5, 1, 'companies', 3, 3, 'Linear Design Language Feedback', 'Karri appreciated the light sky-blue theme and Spartan speed (<5ms response times). They want bidirectional ticket linking enabled.', datetime('now', '-4 days'), datetime('now', '-4 days')),
(6, 1, 'opportunities', 18, 4, 'Vercel Deployment Milestone Met', 'All Next.js edge builds completed in 1.4s. Guillermo verified the integration on Twitter/X. Deal marked Closed Won!', datetime('now', '-6 days'), datetime('now', '-6 days')),
(7, 1, 'people', 7, 1, 'Guillermo Rauch Coffee Chat', 'Discussed frontend cloud evolution, AI agent frameworks, and why lightweight native frameworks like Spartan MVC outperform bloated bloated runtimes.', datetime('now', '-8 days'), datetime('now', '-8 days')),
(8, 1, 'opportunities', 15, 3, 'Monzo Bank Security Audit Passed', 'Chief Information Security Officer approved our cryptographic hashing and token rotation mechanism. Final contract sent to legal.', datetime('now', '-1 days'), datetime('now', '-1 days')),
(9, 1, 'companies', 5, 2, 'Datadog Annual Review', 'Alexis confirmed that Datadog APM tracing hooks into Spartan HTTP pipeline cleanly without requiring C-extension patches.', datetime('now', '-5 days'), datetime('now', '-5 days')),
(10, 1, 'opportunities', 12, 3, 'Cloudflare Enterprise Pricing Review', 'Michelle Zatlyn requested volume discounts on 10M requests tier. Agreed on 15% incentive for 2-year upfront commitment.', datetime('now', '-3 days'), datetime('now', '-3 days')),
(11, 1, 'people', 18, 1, 'Dario Amodei Strategy Briefing', 'Dario stressed the importance of agent tools returning structured JSON schemas so Claude can take actions reliably without hallucination.', datetime('now', '-4 days'), datetime('now', '-4 days')),
(12, 1, 'companies', 6, 3, 'Figma Design Tokens Integration', 'Sho Kuwamoto noted the clean contrast ratios and WCAG AA compliance across our sky-blue color tokens.', datetime('now', '-7 days'), datetime('now', '-7 days')),
(13, 1, 'opportunities', 10, 4, 'Edge Middleware Security Review', 'Malte Ubl reviewed our CSRF token encryption and trusted proxy resolution in Request.php. Everything approved without remarks.', datetime('now', '-5 days'), datetime('now', '-5 days')),
(14, 1, 'companies', 10, 1, 'Anthropic AI Agent Governance Notes', 'Configured strict rate limits and audit trails. Every agent action logs user_id and parameters to the immutable activity log.', datetime('now', '-2 days'), datetime('now', '-2 days')),
(15, 1, 'people', 3, 2, 'Paul Copplestone Founder Catchup', 'Paul mentioned Supabase launches next month. Suggested co-marketing blog post highlighting agentic database orchestration.', datetime('now', '-6 days'), datetime('now', '-6 days')),
(16, 1, 'opportunities', 9, 1, 'Stripe Multi-Currency Agreement Review', 'Legal counsel has redlined clause 4.2 regarding FX conversion timing. Revised draft delivered to Claire Hughes.', datetime('now', '-1 days'), datetime('now', '-1 days')),
(17, 1, 'companies', 15, 3, 'Monzo Open Banking API Integration', 'Technical walkthrough completed with TS Anil. Production cutover scheduled for end of next quarter.', datetime('now', '-3 days'), datetime('now', '-3 days')),
(18, 1, 'people', 20, 2, 'Mistral AI Evaluation Kickoff', 'Arthur Mensch confirmed interest in using CRX to manage European enterprise client engagements for Mistral Large.', datetime('now', '-2 days'), datetime('now', '-2 days'));

-- 13. Custom Fields (Companies, Contacts, Opportunities)
INSERT INTO custom_fields (id, workspace_id, entity_type, name, code, type, options, is_required, order_column, created_at, updated_at) VALUES 
(1, 1, 'companies', 'Lead Source', 'lead_source', 'select', '["Website Inbound","Executive Referral","VC Introduction","Conference / Event","Direct Outreach"]', 0, 1, datetime('now'), datetime('now')),
(2, 1, 'companies', 'Account Tier', 'account_tier', 'select', '["Tier 1 - Strategic ($100k+)","Tier 2 - Enterprise ($50k+)","Tier 3 - Growth ($10k+)"]', 0, 2, datetime('now'), datetime('now')),
(3, 1, 'companies', 'SLA Level', 'sla_level', 'select', '["Platinum (24/7 Dedicated)","Gold (4hr Response)","Silver (Next Business Day)"]', 0, 3, datetime('now'), datetime('now')),
(4, 1, 'people', 'Preferred Channel', 'preferred_channel', 'select', '["Email","Slack Connect","Direct Phone","Signal / WhatsApp"]', 0, 1, datetime('now'), datetime('now')),
(5, 1, 'people', 'Buying Role', 'buying_role', 'select', '["Economic Decision Maker","Technical Champion","Procurement / Legal","End User Influencer"]', 0, 2, datetime('now'), datetime('now')),
(6, 1, 'opportunities', 'Deal Priority', 'deal_priority', 'select', '["Critical / Board Level","High Priority","Standard","Low / Backlog"]', 0, 1, datetime('now'), datetime('now')),
(7, 1, 'opportunities', 'Primary Competitor', 'primary_competitor', 'select', '["HubSpot Enterprise","Salesforce Einstein","Attio CRM","In-House Custom Build","None"]', 0, 2, datetime('now'), datetime('now'));

-- 14. Custom Field Values
INSERT INTO custom_field_values (id, workspace_id, custom_field_id, entity_type, entity_id, text_value, number_value, date_value, json_value, created_at, updated_at) VALUES 
-- Stripe (Company 1)
(1, 1, 1, 'companies', 1, 'Executive Referral', NULL, NULL, NULL, datetime('now'), datetime('now')),
(2, 1, 2, 'companies', 1, 'Tier 1 - Strategic ($100k+)', NULL, NULL, NULL, datetime('now'), datetime('now')),
(3, 1, 3, 'companies', 1, 'Platinum (24/7 Dedicated)', NULL, NULL, NULL, datetime('now'), datetime('now')),
-- Supabase (Company 2)
(4, 1, 1, 'companies', 2, 'VC Introduction', NULL, NULL, NULL, datetime('now'), datetime('now')),
(5, 1, 2, 'companies', 2, 'Tier 2 - Enterprise ($50k+)', NULL, NULL, NULL, datetime('now'), datetime('now')),
-- Anthropic (Company 10)
(6, 1, 1, 'companies', 10, 'Executive Referral', NULL, NULL, NULL, datetime('now'), datetime('now')),
(7, 1, 2, 'companies', 10, 'Tier 1 - Strategic ($100k+)', NULL, NULL, NULL, datetime('now'), datetime('now')),
(8, 1, 3, 'companies', 10, 'Platinum (24/7 Dedicated)', NULL, NULL, NULL, datetime('now'), datetime('now')),
-- Patrick Collison (Person 1)
(9, 1, 4, 'people', 1, 'Slack Connect', NULL, NULL, NULL, datetime('now'), datetime('now')),
(10, 1, 5, 'people', 1, 'Economic Decision Maker', NULL, NULL, NULL, datetime('now'), datetime('now')),
-- Dario Amodei (Person 18)
(11, 1, 4, 'people', 18, 'Signal / WhatsApp', NULL, NULL, NULL, datetime('now'), datetime('now')),
(12, 1, 5, 'people', 18, 'Economic Decision Maker', NULL, NULL, NULL, datetime('now'), datetime('now')),
-- Anthropic High-Throughput Deal (Opportunity 14)
(13, 1, 6, 'opportunities', 14, 'Critical / Board Level', NULL, NULL, NULL, datetime('now'), datetime('now')),
(14, 1, 7, 'opportunities', 14, 'None', NULL, NULL, NULL, datetime('now'), datetime('now')),
-- Stripe Deal (Opportunity 9)
(15, 1, 6, 'opportunities', 9, 'High Priority', NULL, NULL, NULL, datetime('now'), datetime('now')),
(16, 1, 7, 'opportunities', 9, 'Attio CRM', NULL, NULL, NULL, datetime('now'), datetime('now')),
-- Linear Opportunity (Opportunity 3)
(17, 1, 6, 'opportunities', 3, 'High Priority', NULL, NULL, NULL, datetime('now'), datetime('now')),
(18, 1, 7, 'opportunities', 3, 'HubSpot Enterprise', NULL, NULL, NULL, datetime('now'), datetime('now'));

-- 15. Activity Log (Audit trail of events)
INSERT INTO activity_log (id, workspace_id, user_id, action, entity_type, entity_id, description, metadata, created_at) VALUES 
(1, 1, 1, 'created', 'companies', 1, 'Alex Mercer added account Stripe Financial ($145M revenue)', '{"industry":"FinTech"}', datetime('now', '-10 days')),
(2, 1, 1, 'created', 'opportunities', 17, 'Alex Mercer opened deal Global Merchant API Integration ($125,000)', '{"amount":125000}', datetime('now', '-9 days')),
(3, 1, 1, 'stage_changed', 'opportunities', 17, 'Deal Global Merchant API Integration advanced to closed_won', '{"from":"negotiation","to":"closed_won"}', datetime('now', '-5 days')),
(4, 1, 2, 'created', 'companies', 2, 'Sarah Jenkins added account Supabase Inc', '{"industry":"Developer Tools"}', datetime('now', '-8 days')),
(5, 1, 2, 'created', 'opportunities', 13, 'Sarah Jenkins opened deal Postgres Enterprise Read Replica Licensing ($88,000)', '{"amount":88000}', datetime('now', '-7 days')),
(6, 1, 3, 'created', 'companies', 3, 'Marcus Brody added account Linear Orbit', '{"industry":"SaaS"}', datetime('now', '-7 days')),
(7, 1, 3, 'created', 'opportunities', 5, 'Marcus Brody opened deal Sprint Tracking Automation Connector ($38,000)', '{"amount":38000}', datetime('now', '-6 days')),
(8, 1, 1, 'created', 'companies', 10, 'Alex Mercer added account Anthropic Research', '{"industry":"AI"}', datetime('now', '-5 days')),
(9, 1, 1, 'created', 'opportunities', 14, 'Alex Mercer opened deal Claude 3.5 Sonnet High-Throughput Tier ($240,000)', '{"amount":240000}', datetime('now', '-4 days')),
(10, 1, 1, 'stage_changed', 'opportunities', 14, 'Alex Mercer moved Claude 3.5 deal to Negotiation (Probability 85%)', '{"stage":"negotiation"}', datetime('now', '-1 days')),
(11, 1, 5, 'agent_action', 'companies', 13, 'CRX Autonomous Agent enriched Shopify account details and executives', '{"agent":"mcp_enrich"}', datetime('now', '-3 hours')),
(12, 1, 5, 'agent_action', 'opportunities', 3, 'CRX Autonomous Agent scheduled task for Merchant Checkout Extension', '{"task_id":12}', datetime('now', '-1 hours'));

-- 16. Personal Access Tokens (Agent MCP & API Tokens)
INSERT INTO personal_access_tokens (id, workspace_id, user_id, name, token, abilities, last_used_at, expires_at, created_at, updated_at) VALUES 
(1, 1, 1, 'Autonomous AI Agent MCP Key', 'crx_mcp_live_token_secret_12345', '["*"]', datetime('now'), NULL, datetime('now'), datetime('now')),
(2, 1, 1, 'Zapier & Make Automation Webhook Token', 'crx_zapier_live_token_secret_99887', '["companies:read","people:read","opportunities:write"]', datetime('now'), NULL, datetime('now'), datetime('now')),
(3, 1, 2, 'Mobile Sales Assistant Key', 'crx_mobile_sales_token_abc555', '["*"]', NULL, NULL, datetime('now'), datetime('now'));
