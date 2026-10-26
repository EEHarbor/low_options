#!/usr/bin/env python3
"""Check fresh PHPUnit output, startup settings and prethrow diagnostic receipts."""
import base64
import hashlib
import json
import os
from pathlib import Path
import re
import shutil
import signal
import subprocess
import sys
import tempfile
import time
import xml.etree.ElementTree as ET

ROOT = Path(__file__).resolve().parents[2]
PHP = os.environ.get('PHP_BINARY', '/Users/tomjaeger/Library/Application Support/Herd/bin/php82')
TOOL = ROOT / 'tests/.phpunit/phpunit-9.phar'
DIAGNOSTIC = re.compile(rb'^(?:PHP )?(?:PHP Startup:|Startup:|Warning:|Deprecated:|Notice:|Parse error:|Fatal error:|Strict Standards:)', re.M)
NATIVE_HEADERS = re.compile(DIAGNOSTIC.pattern + rb'[^\r\n]*', re.M)


def literal_line(path, text):
    """Bind synthetic harness events to their actual unique authored statement."""
    lines = [index for index, line in enumerate(path.read_text().splitlines(), 1) if text in line]
    if len(lines) != 1:
        raise RuntimeError('Harness sentinel statement changed.')
    return lines[0]


def main():
    """Retain the complete owned run even when checked validation rejects it."""
    if hashlib.sha256(TOOL.read_bytes()).hexdigest() != '6b5235f282c6ccab3674ac27c5b1ddbd1da838e74bf07002ddcfd030d31298d7':
        raise RuntimeError('Pinned PHPUnit checksum mismatch.')
    parent = os.environ.get('LOW_OPTIONS_EVIDENCE_ROOT')
    run = Path(tempfile.mkdtemp(prefix='low-options-checked-', dir=parent))
    journal = run / 'diagnostics.jsonl'
    journal.touch(mode=0o600, exist_ok=False)
    report = run / 'junit.xml'
    arguments = sys.argv[1:]
    requested = None
    for index, argument in enumerate(arguments):
        if argument == '--log-junit':
            requested = arguments[index + 1]
        if argument.startswith('--log-junit='):
            requested = argument.split('=', 1)[1]
    listing = any(argument in ('--help', '-h', '--version', '--manifest', '--sbom', '--composer-lock', '--list-tests', '--list-groups', '--list-suites') or argument.startswith('--list-tests-xml') for argument in arguments)
    command = [PHP, '-d', 'error_reporting=-1', '-d', 'display_errors=stderr', '-d', 'display_startup_errors=1', '-d', 'log_errors=0', '-d', 'html_errors=0', '-d', 'auto_prepend_file=' + str(ROOT / 'tests/Support/Diagnostics.php'), str(TOOL), '--do-not-cache-result', *arguments]
    if not listing:
        command += ['--log-junit', str(report)]
    environment = os.environ.copy()
    environment['LOW_OPTIONS_DIAGNOSTIC_JOURNAL'] = str(journal)
    process = subprocess.Popen(command, cwd=ROOT, env=environment, stdin=subprocess.DEVNULL, stdout=subprocess.PIPE, stderr=subprocess.PIPE, start_new_session=True)
    started = time.time()
    timed_out = False
    interrupted = 0
    interruption_deadline = None

    def interrupt(number, _frame):
        """Signal only this invocation's still-owned PHP session."""
        nonlocal interrupted, interruption_deadline
        interrupted = interrupted or number
        interruption_deadline = interruption_deadline or time.monotonic() + 2
        if process.returncode is None:
            try:
                os.killpg(process.pid, signal.SIGTERM)
            except ProcessLookupError:
                pass

    previous_handlers = {number: signal.signal(number, interrupt) for number in (signal.SIGINT, signal.SIGTERM)}
    deadline = time.monotonic() + 600
    try:
        while True:
            try:
                stdout, stderr = process.communicate(timeout=.2)
                break
            except subprocess.TimeoutExpired:
                if time.monotonic() >= deadline or (interruption_deadline is not None and time.monotonic() >= interruption_deadline):
                    timed_out = True
                    os.killpg(process.pid, signal.SIGKILL)
                    stdout, stderr = process.communicate()
                    break
    finally:
        for number, handler in previous_handlers.items():
            signal.signal(number, handler)
    (run / 'stdout').write_bytes(stdout)
    (run / 'stderr').write_bytes(stderr)
    sys.stdout.buffer.write(stdout)
    sys.stderr.buffer.write(stderr)
    issues = ['Owned suite deadline exceeded.'] if timed_out else []
    if interrupted:
        issues.append('Owned execution interrupted by signal ' + str(interrupted))
    try:
        os.killpg(process.pid, 0)
        issues.append('Owned process group remains after suite completion.')
    except ProcessLookupError:
        pass
    records = [json.loads(line) for line in journal.read_bytes().splitlines()]
    record_kinds = {'startup', 'diagnostic', 'native-start', 'native-spawn', 'native-reaped', 'native-end', 'native-termination', 'native-aborted', 'native-cleanup'}
    if any(record['kind'] not in record_kinds for record in records):
        issues.append('Checked authority changed or an unknown journal record was retained.')
    startups = [record for record in records if record['kind'] == 'startup']
    if not startups or any(record['error_reporting'] != -1 or record['display_errors'] != 'stderr' or record['display_startup_errors'] != '1' or os.path.realpath(record['binary']) != os.path.realpath(PHP) for record in startups):
        issues.append('Missing or nonstrict actual startup receipt.')
    archive_hash = hashlib.sha256((ROOT / 'low_options/low_options.phar').read_bytes()).hexdigest()
    if any(record['archive_sha256'] != archive_hash for record in startups):
        issues.append('Archive input changed during execution.')
    cases = []
    counts = None
    if not listing:
        if not report.is_file() or report.stat().st_mtime < started:
            issues.append('No fresh JUnit report.')
        else:
            document = ET.parse(report).getroot()
            suite = document if document.tag == 'testsuite' else document.find('testsuite')
            cases = list(document.iter('testcase'))
            counts = {key: int(suite.get(key, 0)) for key in ('tests', 'assertions', 'errors', 'failures', 'warnings', 'skipped')}
            if not cases or counts['tests'] != len(cases):
                issues.append('Zero or inconsistent JUnit cases.')
            if requested:
                destination = Path(requested).resolve()
                if destination.exists() or destination.is_symlink():
                    raise RuntimeError('Requested JUnit destination must be fresh.')
                if destination.is_relative_to(ROOT) and not destination.is_relative_to(ROOT / 'tests/reports/runs'):
                    raise RuntimeError('Requested JUnit destination overlaps repository inputs.')
                shutil.copyfile(report, destination)
    identities = [(case.get('class'), case.get('name')) for case in cases]
    main_expected = identities.count(('LowOptionsTests\\DiagnosticHarnessTest', 'testSuppressedSourceDeprecationsAreNotDiscarded'))
    native_expected = identities.count(('LowOptionsTests\\NativeProcessContractTest', 'testSuppressedDiagnosticRemainsVisible'))
    timeout_expected = identities.count(('LowOptionsTests\\NativeProcessContractTest', 'testTimeoutCleanup'))
    harness = ROOT / 'tests/tests/Unit/DiagnosticHarnessTest.php'
    stub = ROOT / 'tests/scripts/stub-probe.php'
    main_line = literal_line(harness, '$handler(E_USER_DEPRECATED,')
    native_line = literal_line(stub, "@trigger_error('native strict sentinel'")
    observed = {'main': 0, 'native': 0}
    native_sentinel_pids = set()
    for record in records:
        if record['kind'] != 'diagnostic':
            continue
        message = base64.b64decode(record['message_base64'], validate=True)
        file = os.fsdecode(base64.b64decode(record['file_base64'], validate=True))
        if record['severity'] == 16384 and file == str(ROOT / 'low_options/pi.low_options.php') and record['line'] == 1 and message == b'intentional harness probe' and record['caller_file'] == str(harness) and record['caller_line'] == main_line:
            observed['main'] += 1
            continue
        if record['severity'] == 16384 and file == str(stub) and record['line'] == native_line and message == b'native strict sentinel' and record['source_callsite']:
            observed['native'] += 1
            native_sentinel_pids.add(record['pid'])
            continue
        issues.append('Unexpected retained diagnostic: ' + repr(record))
    if observed != {'main': main_expected, 'native': native_expected}:
        issues.append('Synthetic harness receipt counts disagree with fresh JUnit identities.')
    starts = {record['id']: record for record in records if record['kind'] == 'native-start'}
    spawns = {record['id']: record for record in records if record['kind'] == 'native-spawn'}
    expected_pids = {process.pid} | {record['pid'] for record in spawns.values()}
    if set(spawns) != set(starts) or {record['pid'] for record in startups} != expected_pids or len(startups) != len(expected_pids):
        issues.append('Missing or duplicate startup receipt for an actual native child.')
    ends = {record['id']: record for record in records if record['kind'] == 'native-end'}
    reaped = {record['id']: record for record in records if record['kind'] == 'native-reaped'}
    terminations = {record['id']: record for record in records if record['kind'] == 'native-termination'}
    aborts = {record['id']: record for record in records if record['kind'] == 'native-aborted'}
    cleanup = {record['id']: record for record in records if record['kind'] == 'native-cleanup'}
    for kind in ('native-start', 'native-spawn', 'native-reaped', 'native-end', 'native-termination', 'native-aborted', 'native-cleanup'):
        identifiers = [record['id'] for record in records if record['kind'] == kind]
        if len(identifiers) != len(set(identifiers)):
            issues.append('Duplicate native invocation record: ' + kind)
    if set(reaped) != set(ends) or set(terminations) != set(aborts) or set(ends) & set(aborts) or set(ends) | set(aborts) != set(starts):
        issues.append('Native final outcomes do not match actual attempts and reaping.')
    if set(starts) != set(cleanup) or any(not record['removed'] for record in cleanup.values()):
        issues.append('Native ownership cleanup is incomplete.')
    aborted = 0
    for identifier, start in starts.items():
        end = ends.get(identifier)
        if end is None:
            aborted_record = aborts.get(identifier)
            if start['command'][-3:-1] != [str(stub), 'wait'] or cleanup[identifier]['completed'] or aborted_record is None:
                issues.append('Unexpected aborted native process.')
            elif aborted_record['stdout_base64'] != '' or aborted_record['stderr_base64'] != '':
                issues.append('Unexpected output from the owned timeout control.')
            termination = terminations.get(identifier)
            if termination is None or aborted_record is None or aborted_record['termination'] != {key: value for key, value in termination.items() if key not in ('kind', 'id')}:
                issues.append('Missing or inconsistent native termination receipt.')
            elif not termination['requested'] or termination['status']['running'] or not termination['status']['signaled'] or termination['status']['termsig'] != 9 or termination['status']['pid'] != spawns[identifier]['pid']:
                issues.append('Native timeout was not reaped with its recorded owned termination.')
            aborted += 1
            continue
        final = reaped.get(identifier)
        if final is None or final['status']['running'] or final['status']['pid'] != end['pid'] or final['status']['pid'] != spawns[identifier]['pid']:
            issues.append('Missing or inconsistent native reaping receipt.')
        elif end['exit'] != (final['status']['exitcode'] if final['status']['exitcode'] != -1 else final['close_status']):
            issues.append('Native result disagrees with its actual final status.')
        output = base64.b64decode(end['stdout_base64'], validate=True) + b'\n' + base64.b64decode(end['stderr_base64'], validate=True)
        synthetic = start['command'][-2:] == [str(stub), 'diagnostic'] and end['pid'] in native_sentinel_pids and end['exit'] != 0
        if end['exit'] != 0 and not synthetic:
            issues.append('Unexpected native exit: ' + repr(end['exit']))
        headers = NATIVE_HEADERS.findall(output)
        expected_fatal = b'Fatal error: Uncaught ErrorException: native strict sentinel in ' + os.fsencode(stub) + b':' + str(native_line).encode()
        if headers and (not synthetic or headers != [expected_fatal]):
            issues.append('Unexpected native diagnostic output.')
    if aborted != timeout_expected:
        issues.append('Native timeout controls disagree with fresh JUnit identities.')
    if DIAGNOSTIC.search(stdout + b'\n' + stderr):
        issues.append('Unexpected parent startup/runtime diagnostic output.')
    receipt = {'command': command, 'exit': process.returncode, 'checked_exit': 70 if issues else process.returncode, 'junit': str(report), 'counts': counts, 'startup_count': len(startups), 'native_attempts': len(starts), 'native_completed': len(ends), 'native_cleanup': len(cleanup), 'expected_harness_diagnostics': observed, 'issues': issues, 'journal_sha256': hashlib.sha256(journal.read_bytes()).hexdigest(), 'stdout_sha256': hashlib.sha256(stdout).hexdigest(), 'stderr_sha256': hashlib.sha256(stderr).hexdigest(), 'archive_sha256': archive_hash}
    (run / 'receipt.json').write_text(json.dumps(receipt, indent=2) + '\n')
    print('\nLOW_OPTIONS_RECEIPT=' + str(run / 'receipt.json'), file=sys.stderr)
    if issues:
        print('Checked validation rejected ' + str(len(issues)) + ' unexpected outcomes.', file=sys.stderr)
    return receipt['checked_exit']


if __name__ == '__main__':
    raise SystemExit(main())
