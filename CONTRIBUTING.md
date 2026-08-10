# Contributing to Chatify

Thank you for contributing to Chatify. This guide covers local development, pull requests, releases, and repository settings.

## Branch targets

| Branch | Purpose |
| ------ | ------- |
| `main` | Active development and releases |

Open all pull requests against **`main`**.

## Development setup

### Requirements

- PHP 8.2+
- Composer
- Node.js 20 (see `frontend/.nvmrc`)
- SQLite extension (used by the test suite)

### Install

```bash
composer install
cd frontend && npm ci
```

### Run tests

```bash
# PHP (PHPUnit)
composer test

# Frontend (Vitest)
cd frontend && npm test

# Frontend typecheck + production build
cd frontend && npm run build
```

If you change anything under `frontend/src/`, rebuild and commit `dist/`:

```bash
cd frontend && npm run build
git add dist/
```

CI will fail if committed `dist/` does not match a fresh build.

## Pull requests

1. Fork and create a branch from `main`.
2. Make focused changes with tests where appropriate.
3. Ensure all checks pass locally before opening a PR.
4. Fill out the PR template checklist.

### Required CI checks

Every PR must pass:

- `php-tests`
- `frontend-tests`
- `verify-dist`

## Branch protection (maintainers)

After CI is configured, enable in **GitHub → Settings → Branches** for `main`:

- Require a pull request before merging
- Require status checks: `php-tests`, `frontend-tests`, `verify-dist`
- Enable **Security advisories** under **Settings → Security**

These settings cannot be committed as files; configure them in the GitHub UI.

## Release checklist

### Beta release (e.g. `v2.0.0-beta.3`)

- [ ] CI green on `main`
- [ ] `composer test`, `npm test`, and `npm run build` pass locally
- [ ] `dist/` committed and matches build
- [ ] Bump `version` in `composer.json` and `frontend/package.json`
- [ ] Tag: `git tag -a v2.0.0-beta.3 -m "Chatify v2.0.0-beta.3"`
- [ ] Push tag: `git push origin v2.0.0-beta.3`
- [ ] Create GitHub Release (mark as **pre-release**)
- [ ] Confirm Packagist picked up the tag

### Stable release (e.g. `v2.0.0`)

- [ ] Beta feedback addressed
- [ ] CI green on `main`
- [ ] Tag `v2.0.0` (pre-release **unchecked**)
- [ ] Publish GitHub Release with upgrade notes

## Getting help

- [Documentation](https://www.chatifyphp.com/docs)
- [GitHub Issues](https://github.com/munafio/chatify/issues)

## Security

See [SECURITY.md](SECURITY.md) for vulnerability reporting. Do not open public issues for security exploits.
