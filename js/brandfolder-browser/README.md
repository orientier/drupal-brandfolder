# Brandfolder Browser

This directory contains everything necessary to compose a Brandfolder Browser web component. We use the [Lit](https://lit.dev) library to speed up development without adding too much weight to the javascript bundle.

## Setup

Install dependencies:

```bash
npm i
```

## Build

We use a TypeScript compiler to produce JavaScript that runs in modern browsers.

To build the JavaScript version of the component(s):

```bash
npm run build
```

To watch files and rebuild when the files are modified, run the following command:

```bash
npm run build:watch
```

The strictness, etc. of the TypeScript compiler and lit-analyzer are configured in `tsconfig.json`.

## Analyze

To generate a Custom Elements Manifest file (custom-elements.json) that can be used by IDEs to provide autocompletion and type information for custom elements, run:

```bash
npm run analyze
```

## Dev Server

We use modern-web.dev's [@web/dev-server](https://www.npmjs.com/package/@web/dev-server) for previewing the BF browser without additional build steps. Web Dev Server handles resolving Node-style "bare" import specifiers, which aren't supported in browsers. It also automatically transpiles JavaScript and adds polyfills to support older browsers. See [modern-web.dev's Web Dev Server documentation](https://modern-web.dev/docs/dev-server/overview/) for more information.

To run the dev server and open the project in a new browser tab:

```bash
npm run serve
```

There is a development HTML file located at `/dev/index.html` that you can view at http://localhost:8000/dev/index.html. Note that this command will serve your code using Lit's development mode (with more verbose errors). To serve your code against Lit's production mode, use `npm run serve:prod`.

## Editing

If you use VS Code, we highly recommend the [lit-plugin extension](https://marketplace.visualstudio.com/items?itemName=runem.lit-plugin), which enables some extremely useful features for lit-html templates:

- Syntax highlighting
- Type-checking
- Code completion
- Hover-over docs
- Jump to definition
- Linting
- Quick Fixes

The project is set up to recommend lit-plugin to VS Code users if they don't already have it installed.

## Linting

Linting of TypeScript files is provided by [ESLint](eslint.org) and [TypeScript ESLint](https://github.com/typescript-eslint/typescript-eslint). In addition, [lit-analyzer](https://www.npmjs.com/package/lit-analyzer) is used to type-check and lint lit-html templates with the same engine and rules as lit-plugin.

The rules are mostly the recommended rules from each project, but some have been turned off to make LitElement usage easier. The recommended rules are pretty strict, so you may want to relax them by editing `.eslintrc.json` and `tsconfig.json`.

To lint the project run:

```bash
npm run lint
```

## Formatting

[Prettier](https://prettier.io/) is used for code formatting. It is generally configured according to the Lit style. This can be configured in `.prettierrc.json`.

Prettier has not been configured to run when committing files, but this could be added with Husky and `pretty-quick`. See the [prettier.io](https://prettier.io/) site for instructions.


## Bundling and minification

Source files are processed for production via Rollup. The configuration file `rollup.config.js` can be used to control this process. See [Build for production](https://lit.dev/docs/tools/production/) on the Lit site for guidelines/info about building application projects that include LitElement components.
