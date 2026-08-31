# Gates: issue #85 delivery target

OWNS: app/Services/ProductImport/**, config/database.php, tests/Feature/Import/**, docs/**, .github/**, GATES.md

Scope: reconcile GitHub issue #85, Beads epic samples-20260717-7rg, release evidence, and required acceptance checks while leaving #85 open.

- [x] G1: GitHub issue #85 remains open and identifies the 20-child delivery map
      CHECK: gh issue view 85 --repo s-a-c/samples-20260717 --json state,body --jq "if .state == \"OPEN\" and (.body | contains(\"20\")) then \"issue-85-open-map-verified\" else error(\"issue-85 state or child-count marker invalid\") end"
      EXPECT: issue-85-open-map-verified
      EVIDENCE: exit=0; shell=/bin/sh; cwd=/Users/s-a-c/Herd/samples-20260717; path=3e2e38e23c11/33 entries; EXPECT=matched; output-sha256=fcb124e12c95c685de96eeef49a83a642c1495350d4ac34b98554a1e2f526f0e; output-bytes=27

- [x] G2: Beads epic has 20 direct children and no open completed child remains
      CHECK: bd show samples-20260717-7rg --json | node -e "let s=''; process.stdin.on('data',c=>s+=c).on('end',()=>{const rows=JSON.parse(s); const epic=rows[0]; if(!epic || epic.status !== 'open' || epic.external_ref !== 'gh-85' || epic.dependent_count !== 20) throw new Error('epic status, external_ref, or child count invalid'); console.log('beads-epic-20-child-map-verified')})"
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
      EVIDENCE: 2026-08-31 Herd acceptance recorded on committed PR #126 SHA 59ac5a010df6ead0198812c85695a55b63d2448a; merged baseline evidence points to PR #107 SHA 420434c8ae1f811d97c34a2d62f222479f02cb51 and PR #110 SHA 4210e5bfaa865e183559a7c81260b555306b85f6.

- [x] G7: all #85-related PR checks and required workflow runs are resolved, with no unexplained failure or pending result
      EVIDENCE: PR #126 final run on 59ac5a010df6ead0198812c85695a55b63d2448a has 10 successful checks, 2 intentional skips, 0 failing, and 0 pending; PRs #107 and #110 merged with required checks passing.

- [x] G8: runtime acceptance covers real-data imports, PostgreSQL 18 with pgvector, shadow-schema isolation and reset/recovery, search projections and Golden Search Corpus, admin imports/statistics, Herd macOS, and Linux CI
      EVIDENCE: 2026-08-31 Herd imports and resets succeeded for Chinook, Northwind, and Pagila; source parity and projections are recorded in #85; focused import/reset/search/Admin tests and full Pest 589/589 passed; pgsql:check, migrations, PHPStan, Pint, Mago guard, architecture, build, and PR #126 Linux CI passed.
