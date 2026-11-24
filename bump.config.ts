import { execFileSync } from 'node:child_process'
import { readFileSync, writeFileSync } from 'node:fs'
import { defineConfig } from 'bumpp'

const file = 'composer.json'
const versionRegex = /^(\s*"version":\s*)"[^"]*"/m

// Only touch the top-level "version" field, not dependency constraints that
// happen to contain the old version string.
export default defineConfig({
  currentVersion: JSON.parse(readFileSync(file, 'utf8')).version,
  files: [],
  execute: ({ state }) => {
    const content = readFileSync(file, 'utf8')
    writeFileSync(file, content.replace(versionRegex, `$1"${state.newVersion}"`))
    execFileSync('git', ['add', file])
  },
})
