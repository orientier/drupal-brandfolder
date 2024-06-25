/**
 * @license
 * Copyright 2018 Google LLC
 * SPDX-License-Identifier: BSD-3-Clause
 */

// import {copy} from '@web/rollup-plugin-copy';
import minifyHTML from 'rollup-plugin-minify-html-literals'
import resolve from '@rollup/plugin-node-resolve'
import replace from '@rollup/plugin-replace'
import summary from 'rollup-plugin-summary'
import {terser} from 'rollup-plugin-terser'
import {globSync} from 'glob'
import path from 'node:path'
import {fileURLToPath} from 'node:url'
import tsPlugin from '@rollup/plugin-typescript'

export default {
  // input: Object.fromEntries(
  //   globSync('src/**/*.ts').map((file) => [
  //     // This removes `src/` as well as the file extension from each
  //     // file, so e.g. src/nested/foo.js becomes nested/foo
  //     path.relative(
  //       'src',
  //       file.slice(0, file.length - path.extname(file).length)
  //     ),
  //     // This expands the relative paths to absolute paths, so e.g.
  //     // src/nested/foo becomes /project/src/nested/foo.js
  //     fileURLToPath(new URL(file, import.meta.url)),
  //   ])
  // ),
  // output: {
  //   dir: 'dist',
  //   format: 'esm',
  // },
  input: 'src/brandfolder-browser.ts',
  output: {
    file: 'dist/brandfolder-browser-bundled.js',
    format: 'esm',
  },
  plugins: [
    replace({'Reflect.decorate': 'undefined'}),
    // Resolve bare module specifiers to relative paths
    resolve(),
    // Minify HTML template literals
    minifyHTML(),
    // // Minify JS
    terser({
      ecma: 2021,
      module: true,
      warnings: true,
    }),
    // Print bundle summary
    summary(),
    // Optional: copy any static assets to build directory
    // copy({
    //   patterns: ['images/**/*'],
    // }),
    tsPlugin(),
  ],
}
