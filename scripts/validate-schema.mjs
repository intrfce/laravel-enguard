// Validates schema/env.schema.json (compiles as draft 2020-12), asserts the
// worked example passes, and that known-bad manifests are rejected.
// Requires: npm install ajv ajv-formats
import Ajv2020 from 'ajv/dist/2020.js'
import addFormats from 'ajv-formats'
import { readFileSync } from 'node:fs'
import { fileURLToPath } from 'node:url'
import { dirname, join } from 'node:path'

const root = join(dirname(fileURLToPath(import.meta.url)), '..')
const schema = JSON.parse(readFileSync(join(root, 'schema/env.schema.json'), 'utf8'))
const example = JSON.parse(readFileSync(join(root, 'docs/example-env.json'), 'utf8'))

const ajv = new Ajv2020({ allErrors: true, strict: false })
addFormats(ajv)

let failures = 0
const check = (label, ok) => {
  console.log(`${ok ? '✓' : '✗'} ${label}`)
  if (!ok) failures++
}

let validate
try {
  validate = ajv.compile(schema)
  check('schema compiles as draft 2020-12', true)
} catch (e) {
  check(`schema compiles (${e.message})`, false)
  process.exit(1)
}

const okExample = validate(example)
check('docs/example-env.json validates', okExample)
if (!okExample) console.log(JSON.stringify(validate.errors, null, 2))

const bad = [
  ["typo'd rule name", { environments: ['local'], variables: { FOO: { rules: { default: { requird: true } } } } }],
  ['unknown top-level key', { environments: ['local'], variables: {}, extra: 1 }],
  ['bad type enum', { environments: ['local'], variables: { FOO: { rules: { default: { type: 'float' } } } } }],
  ['missing environments', { variables: {} }],
]
for (const [label, doc] of bad) {
  check(`rejects: ${label}`, !validate(doc))
}

process.exit(failures === 0 ? 0 : 1)
