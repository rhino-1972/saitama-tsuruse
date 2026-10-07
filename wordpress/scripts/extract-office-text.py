#!/usr/bin/env python3
import argparse
import html
import re
import sys
import zipfile
from pathlib import Path
from xml.etree import ElementTree as ET

NS = {
    "w": "http://schemas.openxmlformats.org/wordprocessingml/2006/main",
    "a": "http://schemas.openxmlformats.org/drawingml/2006/main",
    "p": "http://schemas.openxmlformats.org/presentationml/2006/main",
}


def text_from_word_node(node):
    pieces = []
    for child in node.iter():
        tag = child.tag
        if tag == f"{{{NS['w']}}}t" and child.text:
            pieces.append(child.text)
        elif tag == f"{{{NS['w']}}}tab":
            pieces.append("\t")
        elif tag == f"{{{NS['w']}}}br":
            pieces.append("\n")
    return "".join(pieces).strip()


def extract_docx(path):
    with zipfile.ZipFile(path) as zf:
        xml = zf.read("word/document.xml")
    root = ET.fromstring(xml)
    body = root.find("w:body", NS)
    lines = []
    if body is None:
        return ""
    for element in body:
        if element.tag == f"{{{NS['w']}}}p":
            text = text_from_word_node(element)
            if text:
                lines.append(text)
                lines.append("")
        elif element.tag == f"{{{NS['w']}}}tbl":
            for row in element.findall("w:tr", NS):
                cells = []
                for cell in row.findall("w:tc", NS):
                    cells.append(re.sub(r"\s+", " ", text_from_word_node(cell)))
                if any(cells):
                    lines.append("| " + " | ".join(cells) + " |")
            lines.append("")
    return "\n".join(lines).strip() + "\n"


def slide_sort_key(path):
    match = re.search(r"slide(\d+)\.xml$", path)
    return int(match.group(1)) if match else 0


def extract_pptx(path):
    lines = []
    with zipfile.ZipFile(path) as zf:
        slide_names = sorted(
            [name for name in zf.namelist() if re.match(r"ppt/slides/slide\d+\.xml$", name)],
            key=slide_sort_key,
        )
        for idx, name in enumerate(slide_names, start=1):
            root = ET.fromstring(zf.read(name))
            texts = []
            for t in root.findall(".//a:t", NS):
                if t.text:
                    texts.append(t.text.strip())
            lines.append(f"# Slide {idx}")
            deduped = []
            for text in texts:
                if text and (not deduped or deduped[-1] != text):
                    deduped.append(text)
            lines.extend(deduped)
            lines.append("")
    return "\n".join(lines).strip() + "\n"


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument("inputs", nargs="+")
    parser.add_argument("--out-dir", required=True)
    args = parser.parse_args()

    out_dir = Path(args.out_dir)
    out_dir.mkdir(parents=True, exist_ok=True)

    for input_name in args.inputs:
        path = Path(input_name)
        suffix = path.suffix.lower()
        if suffix == ".docx":
            text = extract_docx(path)
        elif suffix == ".pptx":
            text = extract_pptx(path)
        else:
            print(f"Unsupported file: {path}", file=sys.stderr)
            continue
        out_path = out_dir / f"{path.stem}.md"
        out_path.write_text(text, encoding="utf-8")
        print(out_path)


if __name__ == "__main__":
    main()
