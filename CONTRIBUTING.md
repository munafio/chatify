# Contributing to Chatify

Thank you for contributing to Chatify. This guide covers local development, pull requests, releases, and repository settings.

## Branch targets

| Branch | Purpose |
| ------ | ------- |
| `v2` | Active v2 development and beta releases |
| `master` | v1.6.x maintenance until v2 is stable |

Open pull requests against **`v2`** for v2 work. Open PRs against **`master`** only for v1.x maintenance fixes.

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

1. Fork and create a branch from the correct target (`v2` or `master`).
2. Make focused changes with tests where appropriate.
3. Ensure all checks pass locally before opening a PR.
4. Fill out the PR template checklist.

### Required CI checks

Every PR must pass:

- `php-tests`
- `frontend-tests`
- `verify-dist`

## Branch protection (maintainers)

After CI is configured, enable in **GitHub → Settings → Branches** for `master` and `v2`:

- Require a pull request before merging
- Require status checks: `php-tests`, `frontend-tests`, `verify-dist`
- Enable **Security advisories** under **Settings → Security**

These settings cannot be committed as files; configure them in the GitHub UI.

## Release checklist

### Beta release (e.g. `v2.0.0-beta.1`)

- [ ] CI green on `v2`
- [ ] `composer test`, `npm test`, and `npm run build` pass locally
- [ ] `dist/` committed and matches build
- [ ] Bump `version` in `composer.json` and `frontend/package.json`
- [ ] Tag: `git tag -a v2.0.0-beta.1 -m "Chatify v2.0.0-beta.1"`
- [ ] Push tag: `git push origin v2.0.0-beta.1`
- [ ] Create GitHub Release (mark as **pre-release**)
- [ ] Confirm Packagist picked up the tag
- [ ] Announce on Discord
- [ ] Create/use GitHub label `v2-beta` for feedback issues

### Stable release (e.g. `v2.0.0`)

- [ ] Beta feedback addressed
- [ ] CI green on `v2`
- [ ] Tag `v2.0.0` (pre-release **unchecked**)
- [ ] Merge `v2` into `master`
- [ ] Publish GitHub Release with upgrade notes

## Getting help

- [Documentation](https://chatify.munafio.com)
- [Discord](https://discord.gg/RaxyKVykYJ)
- [GitHub Issues](https://github.com/munafio/chatify/issues)

## Security

See [SECURITY.md](SECURITY.md) for vulnerability reporting. Do not open public issues for security exploits.
