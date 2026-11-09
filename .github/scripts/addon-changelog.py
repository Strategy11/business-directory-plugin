#!/usr/bin/env python3
"""Changelog helpers for the add-on release workflow.

Add-on READMEs do not share one changelog format, so new entries copy the
heading and bullet style of the newest existing entry.

Usage:
  addon-changelog.py ensure <readme> <version>  Insert a placeholder entry if <version> has none.
  addon-changelog.py notes <readme> <version>   Print the bullets of the <version> entry.
"""

import re
import sys
from pathlib import Path

MARKER = re.compile(r"^==\s*Changelog\s*==[ \t]*\r?\n?", re.MULTILINE)
VERSION = re.compile(r"\d+(?:\.\d+)+[0-9A-Za-z.-]*")


def read(readme):
    # Bytes keep the original line endings; several add-on READMEs use CRLF.
    return readme.read_bytes().decode()


def changelog_lines(text):
    match = MARKER.search(text)
    if not match:
        raise SystemExit("README is missing the == Changelog == section")
    return match.end(), text[match.end():].splitlines(keepends=True)


def is_heading(line, version=None):
    stripped = line.strip().strip("=").strip()
    if stripped.lower().startswith("version "):
        stripped = stripped[8:].strip()
    found = VERSION.fullmatch(stripped)
    return bool(found) and (version is None or stripped == version)


def ensure(readme, version):
    text = read(readme)
    start, lines = changelog_lines(text)

    if any(is_heading(line, version) for line in lines):
        print(f"Changelog entry for {version} already present.")
        return

    newline = "\r\n" if "\r\n" in text else "\n"
    heading = f"= {version} ="
    bullet = "* "
    offset = start
    position = None
    for line in lines:
        if position is None and is_heading(line):
            heading = VERSION.sub(version, line.strip(), count=1)
            position = offset
        if line.startswith(("- ", "* ")):
            bullet = line[:2]
            break
        offset += len(line)

    entry = f"{heading}{newline}{bullet}Enhancement: Various improvements and fixes.{newline}{newline}"
    if position is None:
        # No entries yet: put the first one on the line after the marker.
        position = start
    readme.write_bytes((text[:position] + entry + text[position:]).encode())
    print(f"Inserted a fallback changelog entry for {version}.")


def notes(readme, version):
    _, lines = changelog_lines(read(readme))
    out = []
    capturing = False
    for line in lines:
        if is_heading(line):
            if capturing:
                break
            capturing = is_heading(line, version)
            continue
        if capturing:
            out.append(line.rstrip())
    print("\n".join(out).strip())


def main():
    if len(sys.argv) != 4 or sys.argv[1] not in ("ensure", "notes"):
        raise SystemExit(__doc__)
    command, readme, version = sys.argv[1], Path(sys.argv[2]), sys.argv[3]
    (ensure if command == "ensure" else notes)(readme, version)


if __name__ == "__main__":
    main()
