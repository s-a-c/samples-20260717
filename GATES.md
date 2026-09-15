# Gates: switch package manager pnpm to bun (bead samples-20260717-b9k)

OWNS: package.json, composer.json, pnpm-workspace.yaml, pnpm-lock.yaml, bun.lock, bunfig.toml, .github/workflows/**, AGENTS.md, CLAUDE.md, GATES.md, docs/10-architecture/1003-adr/100345-package-manager-bun.md

Scope: replace pnpm with bun as the project package manager across manifests, lockfiles, CI workflows, agent instructions, and a recorded ADR, with the shell-quote 1.9.0 override preserved.

- [x] G1: fresh install from bun lockfile succeeds
      CHECK: rm -rf node_modules && bun install --frozen-lockfile && echo bun-install-ok
      EXPECT: bun-install-ok
  EVIDENCE: automatic-evidence=v1; definition-sha256=148e3a2fea1af0dac370469bcbf212d0393abf852f77f1324d71f7e180827b71; exit=0; EXPECT=matched; output-sha256=0565efa059f59c84a171cad93acf1e7d9613d477f97d5c4eee4dfd51275660ce; output-bytes=376; shell=/bin/sh; cwd=/Users/s-a-c/Herd/samples-20260717; path=9007ddfd758e/20 entries

- [x] G2: frontend build succeeds under bun
      CHECK: bun run build
      EXPECT: /(?:built|success|done)/i
  EVIDENCE: automatic-evidence=v1; definition-sha256=7fc50a4d2acf36fb8b6e07207b7e4b3960b51c6be0db30b6cc003a5765ba4a7d; exit=0; EXPECT=matched; output-sha256=c09c6372ec558bfff0c5036ba6461e3c75d154b45dbe370971c38d3566ea341e; output-bytes=2935; shell=/bin/sh; cwd=/Users/s-a-c/Herd/samples-20260717; path=9007ddfd758e/20 entries

- [x] G3: shell-quote override pins 1.9.0 in bun lockfile
      CHECK: node -e "const s=require('fs').readFileSync('bun.lock','utf8'); const m=s.match(/\"shell-quote\":\s*\"([^\"]+)\"/g)||[]; if(!m.length) throw new Error('shell-quote absent from bun.lock'); const bad=m.filter(x=>!/1\.9\.0/.test(x)); if(bad.length) throw new Error('shell-quote not 1.9.0: '+bad.join(', ')); console.log('shell-quote-190-verified')"
      EXPECT: shell-quote-190-verified
  EVIDENCE: automatic-evidence=v1; definition-sha256=ebf1bae93ba0d4e56d5ffc97ddf71fd1b7e87ec6db6bcb8cc2e1e6be3cd8f434; exit=0; EXPECT=matched; output-sha256=c0e4159641392e09d298d3e96537cb6731e3d58cb4bb855235e91af06f151488; output-bytes=25; shell=/bin/sh; cwd=/Users/s-a-c/Herd/samples-20260717; path=9007ddfd758e/20 entries

- [x] G4: composer manifest valid and pnpm-free
      CHECK: composer validate --strict --no-check-publish && ! grep -q pnpm composer.json && echo composer-pnpm-free
      EXPECT: composer-pnpm-free
  EVIDENCE: automatic-evidence=v1; definition-sha256=f12fecf06e2a6dfdee3c78ed88b4e2b6a06898f515a98245791d01019ee0005a; exit=0; EXPECT=matched; output-sha256=d4d13e544565730090e57a6317b0ea08adc92fa6de810feaaacde575d124209b; output-bytes=44; shell=/bin/sh; cwd=/Users/s-a-c/Herd/samples-20260717; path=9007ddfd758e/20 entries

- [x] G5: no pnpm references remain in active config and docs
      CHECK: ! grep -rl pnpm package.json composer.json .github/workflows AGENTS.md CLAUDE.md && echo active-files-pnpm-free
      EXPECT: active-files-pnpm-free
  EVIDENCE: automatic-evidence=v1; definition-sha256=d49c1e2358b71176294492368a7ebf46129874b2b3d33102890d3dd5f665fb32; exit=0; EXPECT=matched; output-sha256=b61024ea4cbf63a75b8a5475fdbd2b37316b4e5b9096c8e2cb2337ab1f54bea6; output-bytes=23; shell=/bin/sh; cwd=/Users/s-a-c/Herd/samples-20260717; path=9007ddfd758e/20 entries

- [x] G6: all workflow YAML files still parse
      CHECK: php -r 'require "vendor/autoload.php"; foreach(["tests.yml","mutation.yml","tia-baseline.yml","codeql.yml","semgrep.yml"] as $f){Symfony\Component\Yaml\Yaml::parseFile(".github/workflows/".$f);} echo "workflows-yaml-valid";'
      EXPECT: workflows-yaml-valid
  EVIDENCE: automatic-evidence=v1; definition-sha256=84058091063785beaba13ed88249740f180b3bb73f95f1b4e7bf6e32c13baf4d; exit=0; EXPECT=matched; output-sha256=47f6019c2a54a293e942b7a94ee41dca6b5232f7ab6ec91ba5bb051a3a411503; output-bytes=20; shell=/bin/sh; cwd=/Users/s-a-c/Herd/samples-20260717; path=9007ddfd758e/20 entries

- [x] G7: bun as package manager recorded in accepted ADR
      CHECK: test -f docs/10-architecture/1003-adr/100345-package-manager-bun.md && grep -q "Status:\*\* Accepted" docs/10-architecture/1003-adr/100345-package-manager-bun.md && echo adr-recorded
      EXPECT: adr-recorded
  EVIDENCE: automatic-evidence=v1; definition-sha256=fb4b0ff7a68bc256eaf2e7fd97ac63fe6bda4a323fa17793952d29e69f9c8bc2; exit=0; EXPECT=matched; output-sha256=d19b882b55e46d1f1c9cd09a95975d918a48e93d634cc9171aa73822eaf8c066; output-bytes=13; shell=/bin/sh; cwd=/Users/s-a-c/Herd/samples-20260717; path=9007ddfd758e/20 entries
