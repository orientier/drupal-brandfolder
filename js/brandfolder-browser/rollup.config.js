import minifyHTML from 'rollup-plugin-minify-html-literals'
import resolve from '@rollup/plugin-node-resolve'
import replace from '@rollup/plugin-replace'
import summary from 'rollup-plugin-summary'
import {terser} from 'rollup-plugin-terser'
import tsPlugin from '@rollup/plugin-typescript'

export default {
  input: 'src/brandfolder-browser.ts',
  output: {
    file: 'dist/brandfolder-browser-bundled.js',
    format: 'esm',
    sourcemap: true,
  },
  plugins: [
    replace({
      'Reflect.decorate': 'undefined',
      preventAssignment: true,
    }),
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
    tsPlugin(),
  ],
}
