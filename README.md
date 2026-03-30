# com_snippets — Joomla Snippets Component

A Joomla 5+ component for managing and displaying reusable code snippets or content blocks, with frontend submission and category support.

- **Author:** René Kreijveld — [github.com/renekreijveld](https://github.com/renekreijveld)
- **Version:** 1.0.2
- **License:** GNU GPL v2+

## Features

- Create, edit, and manage snippets and categories from the frontend
- Category support via Joomla's built-in category system
- Markdown rendering via the bundled `lib_snippets` library
- Frontend views: category list, category detail, single snippet, frontend editing of categories and snippets
- Auto-alias generation from title with Unicode support
- Check-in/check-out locking for concurrent edit prevention
- Access control via Joomla ACL (`core.create`, `core.edit`, `core.edit.state`)
- Multilingual: English (`en-GB`) and Dutch (`nl-NL`)

## Requirements

- Joomla 5+
- PHP 8.4+
- MySQL

## Installation

1. Download the latest `snippets_x.x.x.zip` package from the `Distro/` folder.
2. In the Joomla administrator, go to **System → Install → Extensions**.
3. Upload and install the package file.

The package installs two extensions:

| Extension | Type | Description |
|-----------|------|-------------|
| `com_snippets` | Component | Main snippets component (admin + frontend) |
| `lib_snippets` | Library | Shared library (Markdown renderer) |

## Building the Package

Use the included `package.sh` script to build installable zip files:

```bash
./package.sh
```

Output packages are written to the `Packages/` and `Distro/` directories.

## License

GNU General Public License version 3 or later. See [LICENSE](LICENSE).
