# Gates: issue #85 delivery target

OWNS: app/Services/ProductImport/**, config/database.php, tests/Feature/Import/**, docs/**, .github/**, GATES.md

Scope: reconcile GitHub issue #85, Beads epic samples-20260717-7rg, release evidence, and required acceptance checks while leaving #85 open.

- [x] G1: GitHub issue #85 remains open and identifies the 20-child delivery map
      CHECK: node -e "const cp=require('child_process'); const q='query { repository(owner: \"s-a-c\", name: \"samples-20260717\") { issue(number: 85) { state subIssues(first: 100) { nodes { number state } } } } }'; const i=JSON.parse(cp.execFileSync('gh',['api','graphql','-f',`query=${q}`],{encoding:'utf8'})).data.repository.issue; const expected=[86,87,88,89,90,91,92,93,94,95,96,97,101,102,103,104,105,106,108,111]; const actual=i.subIssues.nodes.map(x=>x.number).sort((a,b)=>a-b); if(i.state!=='OPEN'||actual.length!==expected.length||actual.some((n,index)=>n!==expected[index])) throw new Error('issue-85 state or sub-issue map invalid'); console.log('issue-85-open-map-verified')"
      EXPECT: issue-85-open-map-verified
      EVIDENCE: exit=0; shell=/bin/sh; cwd=/Users/s-a-c/Herd/samples-20260717; path=3e2e38e23c11/33 entries; EXPECT=matched; output-sha256=fcb124e12c95c685de96eeef49a83a642c1495350d4ac34b98554a1e2f526f0e; output-bytes=27

- [x] G2: Beads epic has 20 direct children and no open completed child remains
      CHECK: node -e "const cp=require('child_process'); const epic=JSON.parse(cp.execFileSync('bd',['show','samples-20260717-7rg','--json'],{encoding:'utf8'}))[0]; const children=JSON.parse(cp.execFileSync('bd',['list','--all','--parent','samples-20260717-7rg','--flat','--json'],{encoding:'utf8'})); const expected=['gh-86','gh-87','gh-88','gh-89','gh-90','gh-91','gh-92','gh-93','gh-94','gh-95','gh-96','gh-97','gh-101','gh-102','gh-103','gh-104','gh-105','gh-106','gh-108','gh-111']; const refs=children.map(x=>x.external_ref).sort(); if(epic.status!=='open'||epic.external_ref!=='gh-85'||epic.dependent_count!==20||children.length!==20||children.some(x=>x.status!=='closed')||refs.join(',')!==expected.slice().sort().join(',')) throw new Error('epic or direct children invalid'); console.log('beads-epic-20-child-map-verified')"
      EXPECT: beads-epic-20-child-map-verified
      EVIDENCE: exit=0; shell=/bin/sh; cwd=/Users/s-a-c/Herd/samples-20260717; path=3e2e38e23c11/33 entries; EXPECT=matched; output-sha256=8d03edbd6b61f7ee1a055f1f9d65622a59f5fdc1aa4a37c2a655f1a121ff32a8; output-bytes=33

- [x] G3: repository documentation names PostgreSQL 18 with pgvector as database target and contains no project-database SQLite claim
      CHECK: node -e "const fs=require('fs'); const cp=require('child_process'); const out=cp.execFileSync('rg',['-l','PostgreSQL 18|pgvector','docs','README.md','CONTEXT.md'],{encoding:'utf8'}); if(!out.trim()) throw new Error('target documentation absent'); const stale=cp.spawnSync('rg',['-n','SQLite as (the )?project database|SQLite.*project database|project database.*SQLite','docs','README.md','CONTEXT.md'],{encoding:'utf8'}); if(stale.status===0) throw new Error('stale SQLite project-database claim found'); console.log('database-target-docs-verified')"
      EXPECT: database-target-docs-verified
      EVIDENCE: exit=0; shell=/bin/sh; cwd=/Users/s-a-c/Herd/samples-20260717; path=3e2e38e23c11/33 entries; EXPECT=matched; output-sha256=6a83bdfb6c330f0a877f0258605cbb89a70e39df41790df1fa4cf743b4f91b9a; output-bytes=30

- [x] G4: project quality gates pass on current checkout
      CHECK: source .env.sage && composer phpstan:analyze && composer mago:guard && composer test:arch
      EXPECT: /(?:PASS|OK|success)/i
      EVIDENCE: exit=0; shell=/bin/sh; cwd=/Users/s-a-c/Herd/samples-20260717; path=3e2e38e23c11/33 entries; EXPECT=matched; output-sha256=c991deed15b70369c3b71d681ca1496dae90967a391e2fe69e85c6402855cf0c; output-bytes=187

- [x] G5: frontend build passes on current checkout
      CHECK: pnpm run build
      EXPECT: /(?:built|success|done)/i
      EVIDENCE: exit=0; shell=/bin/sh; cwd=/Users/s-a-c/Herd/samples-20260717; path=3e2e38e23c11/33 entries; EXPECT=matched; output-sha256=da3efd8f187331a68dd1069bf40364a38a98c9e80079179c34373822558e635f; output-bytes=2291

- [x] G6: release acceptance evidence is current and points to committed SHAs
      EVIDENCE: 2026-08-31 Herd acceptance recorded against committed PR #126 SHA 7fc5c55803cb19a1c13e377544365f6d0ce01559; merged baseline evidence points to PR #107 SHA 420434c8ae1f811d97c34a2d62f222479f02cb51 and PR #110 SHA 4210e5bfaa865e183559a7c81260b555306b85f6.

- [x] G7: all #85-related PR checks and required workflow runs are resolved, with no unexplained failure or pending result
      EVIDENCE: PR #126 run for committed SHA 7fc5c55803cb19a1c13e377544365f6d0ce01559 has 10 successful checks, 2 intentional skips, 0 failing, and 0 pending; PRs #107 and #110 merged with required checks passing.

- [x] G8: runtime acceptance covers real-data imports, PostgreSQL 18 with pgvector, shadow-schema isolation and reset/recovery, search projections and Golden Search Corpus, admin imports/statistics, Herd macOS, and Linux CI
      EVIDENCE: 2026-08-31 Herd imports and resets succeeded for Chinook, Northwind, and Pagila; source parity and projections are recorded in #85; focused import/reset/search/Admin tests and full Pest 590/590 passed; pgsql:check, migrations, PHPStan, Pint, Mago guard, architecture, build, and PR #126 Linux CI passed.

- [x] G9: broad Mago analysis has a current committed baseline with no stale entries
      CHECK: vendor/bin/mago analyze --verify-baseline --baseline .mago/analyze-baseline.json && vendor/bin/mago analyze
      EXPECT: /Baseline is up to date|No issues found/
      EVIDENCE: exit=0 on committed SHA 7fc5c55803cb19a1c13e377544365f6d0ce01559; baseline verifies up to date and analyzer reports no unbaselined issues (4,235 legacy findings retained in the reviewed baseline).

- [x] G10: full-tree strict documentation validation passes
      CHECK: documentation-structure check --strict docs
      EXPECT: /compliant: docs/
      EVIDENCE: exit=0 `compliant: docs` on committed SHA 7fc5c55803cb19a1c13e377544365f6d0ce01559.

- [ ] G11: every #85-related PR has no failing or pending checks
      CHECK: node -e "const cp=require('child_process'); for(const n of [107,110,126]) { const rows=JSON.parse(cp.execFileSync('gh',['pr','checks',String(n),'--json','bucket,name'],{encoding:'utf8'})); if(rows.length===0||rows.some(x=>x.bucket==='fail'||x.bucket==='pending'||x.bucket==='cancel')) throw new Error('unresolved checks on PR '+n); } console.log('issue-85-pr-checks-verified')"
      EXPECT: /issue-85-pr-checks-verified/
      EVIDENCE: pending current PR run
