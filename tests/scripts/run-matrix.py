#!/usr/bin/env python3
"""Run every configured test under explicit Herd runtimes without changing Herd."""
import datetime
import json
import os
from pathlib import Path
import subprocess
import tempfile
import xml.etree.ElementTree as ET

ROOT = Path(__file__).resolve().parents[2]
HERD = Path('/Users/tomjaeger/Library/Application Support/Herd/bin')
RUNS = ROOT / 'tests/reports/runs'
RUNS.mkdir(parents=True, exist_ok=True)
run = Path(tempfile.mkdtemp(prefix=datetime.datetime.now().strftime('%Y%m%d-%H%M%S-'), dir=RUNS))
results = []
for version in ('74', '80', '81', '82', '83', '84', '85'):
    php = HERD / ('php' + version)
    report = run / ('php' + version + '.xml')
    command = [str(ROOT / 'tests/.phpunit/run-phpunit.sh'), '-c', 'phpunit.xml',
               '--colors=never', '--log-junit', str(report)]
    environment = dict(os.environ, PHP_BINARY=str(php))
    version_text = subprocess.run([str(php), '-v'], capture_output=True, text=True, check=True).stdout
    completed = subprocess.run(command, cwd=ROOT, env=environment, text=True, stdout=subprocess.PIPE, stderr=subprocess.STDOUT)
    (run / ('php' + version + '.log')).write_text(version_text + '\nCOMMAND: ' + repr(command) + '\n' + completed.stdout)
    if not report.exists():
        raise RuntimeError('No fresh JUnit report for PHP ' + version + ': ' + completed.stdout)
    tree = ET.parse(report)
    suites = tree.getroot().findall('testsuite')
    counts = {key: int(suites[0].get(key, '0')) for key in ('tests', 'assertions', 'errors', 'failures', 'warnings', 'skipped')}
    if counts['tests'] == 0:
        raise RuntimeError('Rejected zero-test run: PHP ' + version)
    counts['risky'] = sum('Risky' in issue.get('type', '') for issue in tree.iter('failure'))
    counts['incomplete'] = sum('Incomplete' in issue.get('type', '') for issue in tree.iter('skipped'))
    result = dict(version=version_text.splitlines()[0], runtime=str(php), exit=completed.returncode,
                  command=command, environment={"PHP_BINARY": str(php)}, counts=counts, log=str(run / ('php' + version + '.log')))
    results.append(result)
    print(json.dumps(result), flush=True)
summary = run / 'matrix.json'
summary.write_text(json.dumps(results, indent=2) + '\n')
print('MATRIX_REPORT=' + str(summary))

raise SystemExit(1 if any(result['exit'] for result in results) else 0)
