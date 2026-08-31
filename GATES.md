# Gates: issue #85 delivery target

OWNS: app/Services/ProductImport/**, config/database.php, tests/Feature/Import/**, docs/**, .github/**, GATES.md

Scope: reconcile GitHub issue #85, Beads epic samples-20260717-7rg, release evidence, and required acceptance checks while leaving #85 open.

- [ ] G1: GitHub issue #85 remains open and identifies the 20-child delivery map
      CHECK: gh issue view 85 --repo s-a-c/samples-20260717 --json state,body --jq "if .state == \"OPEN\" and (.body | contains(\"20\")) then \"issue-85-open-map-verified\" else error(\"issue-85 state or child-count marker invalid\") end"
      EXPECT: issue-85-open-map-verified
      EVIDENCE: pending

- [ ] G2: Beads epic has 20 direct children and no open completed child remains
      CHECK: bd show samples-20260717-7rg --json | node -e "let s=''; process.stdin.on('data',c=>s+=c).on('end',()=>{const rows=JSON.parse(s); const epic=rows[0]; if(!epic || epic.status !== 'open' || epic.external_ref !== 'gh-85' || epic.dependent_count !== 20) throw new Error('epic status, external_ref, or child count invalid'); console.log('beads-epic-20-child-map-verified')})"
      EXPECT: beads-epic-20-child-map-verified
      EVIDENCE: pending

- [ ] G3: repository documentation names PostgreSQL 18 with pgvector as database target and contains no project-database SQLite claim
      CHECK: node -e "const fs=require('fs'); const cp=require('child_process'); const out=cp.execFileSync('rg',['-l','PostgreSQL 18|pgvector','docs','README.md','CONTEXT.md'],{encoding:'utf8'}); if(!out.trim()) throw new Error('target documentation absent'); const stale=cp.spawnSync('rg',['-n','SQLite as (the )?project database|SQLite.*project database|project database.*SQLite','docs','README.md','CONTEXT.md'],{encoding:'utf8'}); if(stale.status===0) throw new Error('stale SQLite project-database claim found'); console.log('database-target-docs-verified')"
      EXPECT: database-target-docs-verified
      EVIDENCE: pending

- [ ] G4: project quality gates pass on current checkout
      CHECK: composer run ci:check --no-interaction
      EXPECT: /(?:PASS|OK|success)/i
      EVIDENCE: pending

- [ ] G5: frontend build passes on current checkout
      CHECK: pnpm run build
      EXPECT: /(?:built|success|done)/i
      EVIDENCE: pending

- [ ] G6: release acceptance evidence is current and points to committed SHAs
      EVIDENCE: pending

- [ ] G7: all #85-related PR checks and required workflow runs are resolved, with no unexplained failure or pending result
      EVIDENCE: pending

- [ ] G8: runtime acceptance covers real-data imports, PostgreSQL 18 with pgvector, shadow-schema isolation and reset/recovery, search projections and Golden Search Corpus, admin imports/statistics, Herd macOS, and Linux CI
      EVIDENCE: pending
