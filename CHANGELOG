## 0.0.2 - 2026.11.xx
Added
- "Misc" settings section with admin menu priority (default 60) that
  controls the position of the Cars menu in the admin sidebar

## 0.0.1 - 2026.11.05
Added
- Cars post type with Vehicle Digital Passport (VIN, license plate,
  production year, engine capacity, power, battery capacity, electric range)
- Brand & model, body type and engine type taxonomies
- Engine-aware passport fields per engine type
- Mileage log per car with admin modal (Car Data / Mileage / Service tabs)
- Service Records post type with service types taxonomy, workshop, cost
  and status; car–record join table
- Frontend data tables (passport, mileage log, service history) with
  per-field visibility (owner / all / hidden)
- Predefined data import: brands & models, body, engine and service types,
  with language-aware presets
- Settings page (general, cars, data)
- Polish translation (pl_PL)
- Plugin Check (PCP) step in the wporg build; build fails on errors
- Symlink of the freshly built wporg release into wp-content/plugins (Linux/macOS)
- Filters and actions prefixed: my-car/... → iworks/my-car/...
- Bun-based build system with wporg and github release ZIPs and .pot/.po tooling

Improved
- Plugin tagline and description in package.json
- Post types loaded by exact names instead of filename guessing
- Hidden files, AGENTS.md, .playwright-mcp/ and (temporarily) the iWorks rate
  library excluded from release ZIPs
- Build order: github ZIP is built before wporg so a Plugin Check failure
  does not block it
- Bootstrap guard prevents double loading when dev copy and built release
  are active side by side

Fixed
- Progress bar gluing step description onto the step label
- Plugin Check silently passing when wp-cli produced no output
- Duplicate function/constant declarations when dev and release copies coexist
