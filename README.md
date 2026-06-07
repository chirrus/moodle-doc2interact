# Doc2Interact for Moodle

> Transform documents into interactive learning content directly inside your Moodle course — powered by AI.

![Moodle](https://img.shields.io/badge/Moodle-4.0%2B-orange) ![PHP](https://img.shields.io/badge/PHP-7.4%2B-blue) ![License](https://img.shields.io/badge/license-GPL--3.0-green)

---

## What it does

Doc2Interact is a Moodle activity module that allows teachers to upload a PDF or DOCX document and automatically generate a full set of learning resources in their course — in under 45 seconds, without leaving Moodle.

**Resources created automatically:**

| Resource | Type in Moodle |
|---|---|
| Interactive content page | Page |
| Offline self-assessment | Page |
| Discussion forum | Forum (Single discussion) |
| Assignment with file submission | Assignment |
| Question bank (MCQ + True/False) | Questions |
| Interactive H5P activity | H5P |

---

## How it works

1. Teacher adds a **Doc2Interact** activity to their course
2. Opens it and uploads a PDF or DOCX file
3. Text is extracted **in the browser** — the file never leaves the teacher's device
4. The plugin sends the text to the Doc2Interact API (powered by Gemini AI)
5. All resources are created automatically in the same course section

---

## Requirements

- Moodle 4.0 or higher (tested on 4.5.4)
- PHP 7.4+
- cURL enabled on the server
- Internet access (for AI processing and CDN libraries)

---

## Installation

1. Download the latest ZIP from the [releases page](../../releases)
2. Go to **Site administration → Plugins → Install plugins**
3. Upload the ZIP and select type **Activity module (mod)**
4. Follow the installation wizard
5. Go to **Site administration → Plugins → Activity modules → Doc2Interact**
6. Configure the API URL and access key

---

## Configuration

| Setting | Description | Default |
|---|---|---|
| API URL | Doc2Interact server endpoint | `https://doc2interact.com` |
| API Key | Institutional access key | `Moodle2026#!` |

The default key allows immediate use with limited credits. For unlimited institutional use, request your own key at [doc2interact.com](https://doc2interact.com).

---

## Permissions

| Role | Can generate | Sees activity |
|---|---|---|
| Admin / Manager | ✅ | ✅ |
| Editing teacher | ✅ | ✅ |
| Non-editing teacher | ❌ | ✅ |
| Student | ❌ | Info message |

---

## Privacy

- Uploaded files are **never sent to the server** — text extraction happens entirely in the teacher's browser
- Only the extracted text is sent to the Doc2Interact API for processing
- No student data is collected or transmitted

---

## External dependencies

- [pdf.js 3.11](https://mozilla.github.io/pdf.js/) — PDF text extraction (CDN)
- [mammoth.js 1.6](https://github.com/mwilliamson/mammoth.js) — DOCX text extraction (CDN)
- [Doc2Interact API](https://doc2interact.com) — AI content generation

---

## About

Developed by **Sergio Pilar** — Educational Technology Specialist  
[doc2interact.com](https://doc2interact.com) · +54 9 362 413 3409

---

## License

This plugin is licensed under the [GNU GPL v3](LICENSE).
