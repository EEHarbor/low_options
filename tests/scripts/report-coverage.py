#!/usr/bin/env python3
"""Report Xdebug source lines, basic blocks, decision exits and terminal caveats."""
import argparse
import hashlib
import json
from pathlib import Path
import xml.etree.ElementTree as ET


def values(value):
    return value.values() if isinstance(value, dict) else value


def items(value):
    return value.items() if isinstance(value, dict) else enumerate(value)


def summarize(lines, functions, start, end):
    selected = {int(line): hit for line, hit in lines.items() if start <= int(line) <= end and hit != -2}
    blocks = []
    outcomes = []
    terminal = []
    for function, graph in functions.items():
        for block_id, block in items(graph.get('branches', {})):
            if not start <= block['line_start'] <= end:
                continue
            detail = dict(function=function, block=block_id, line_start=block['line_start'], line_end=block['line_end'], hit=block['hit'])
            blocks.append(detail)
            exits = list(values(block.get('out', {})))
            hits = list(values(block.get('out_hit', {})))
            for index, destination in enumerate(exits):
                outcome = dict(detail, destination=destination, hit=hits[index] if index < len(hits) else None)
                if destination == 2147483645:
                    terminal.append(outcome)
                elif len(exits) > 1:
                    outcomes.append(outcome)
    return dict(
        line=dict(hit=sum(hit > 0 for hit in selected.values()), total=len(selected), uncovered=[line for line, hit in selected.items() if hit == -1]),
        basic_block=dict(hit=sum(block['hit'] > 0 for block in blocks), total=len(blocks), uncovered=[b for b in blocks if not b['hit']]),
        decision_outcomes=dict(hit=sum(bool(edge['hit']) for edge in outcomes), total=len(outcomes), uncovered=[edge for edge in outcomes if not edge['hit']]),
        synthetic_terminal_outcomes=dict(hit=sum(bool(edge['hit']) for edge in terminal), total=len(terminal), unobserved=[edge for edge in terminal if not edge['hit']]),
        state='measured' if selected or blocks else 'unknown_unexecuted',
    )


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument('raw')
    parser.add_argument('output')
    parser.add_argument('--measurement', required=True)
    parser.add_argument('--clover', required=True)
    args = parser.parse_args()
    root = Path(__file__).resolve().parents[2]
    inventory = json.loads((root / 'tests/reports/inventory.json').read_text())
    raw_path = Path(args.raw)
    raw = json.loads(raw_path.read_text())
    normalized = {}
    for path, graph in raw.items():
        if path.startswith('phar://' + str(root) + '/'):
            name = path[len('phar://' + str(root) + '/'):]
        elif path.startswith(str(root) + '/'):
            name = path[len(str(root)) + 1:]
        else:
            continue
        if name == 'low_options/low_options.phar':
            name += '/<stub>.php'
        normalized[name] = graph
    report = dict(baseline=inventory['baseline'], archive_sha256=inventory['archive_sha256'],
                  runtime='PHP 8.2.30 / Xdebug 3.5.3', raw_sha256=hashlib.sha256(raw_path.read_bytes()).hexdigest(),
                  limitations='Parent-process graph only. Native child probes are not merged. Synthetic terminal exits are retained separately; unobserved terminal exits remain an instrumentation limitation. Unknown/unexecuted units are not 100%.', files={})
    clover = ET.Element('coverage')
    project = ET.SubElement(clover, 'project')
    for name, source in inventory['files'].items():
        graph = normalized.get(name, {'lines': {}, 'functions': {}})
        file_report = dict(sha256=source['sha256'], summary=summarize(graph['lines'], graph.get('functions', {}), 1, 10**9), methods=[])
        for method in source['methods']:
            file_report['methods'].append(dict(method, coverage=summarize(graph['lines'], graph.get('functions', {}), method['start'], method['end'])))
        report['files'][name] = file_report
        copy = Path(args.measurement) / name
        if not copy.exists() or hashlib.sha256(copy.read_bytes()).hexdigest() != source['sha256']:
            raise RuntimeError('Missing or non-identical measurement source: ' + name)
        xml_file = ET.SubElement(project, 'file', name=str(copy.resolve()))
        for line, hit in graph['lines'].items():
            if hit == -2:
                continue
            # The skill's Clover parser reports lines only; raw decision metrics remain authoritative.
            ET.SubElement(xml_file, 'line', num=str(line), type='stmt', count=str(max(hit, 0)))
    Path(args.output).write_text(json.dumps(report, indent=2) + '\n')
    ET.ElementTree(clover).write(args.clover, encoding='utf-8', xml_declaration=True)
    print('COVERAGE_REPORT=' + args.output)
    for name, file in report['files'].items():
        summary = file['summary']
        print(name, 'lines', f"{summary['line']['hit']}/{summary['line']['total']}", 'basic_block', f"{summary['basic_block']['hit']}/{summary['basic_block']['total']}", 'decision_outcomes', f"{summary['decision_outcomes']['hit']}/{summary['decision_outcomes']['total']}")


if __name__ == '__main__':
    main()
