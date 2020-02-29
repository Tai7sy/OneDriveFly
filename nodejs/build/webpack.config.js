
const fs = require('fs');
const path = require('path');
const webpack = require('webpack');

// const nodeExternals = require('webpack-node-externals');

function resolve (...dir) {
  return path.join(__dirname, '..', ...dir)
}

module.exports = {
  entry: [
    resolve('src/index.js')
  ],
  target: 'node',
  node: {
    __dirname: false
  },
  externals: {
    'axios': 'commonjs axios',
    'noogger': 'commonjs noogger',
  },
  devtool: 'source-map',
  output: {
    path: resolve('dist'),
    filename: 'app.js',
    publicPath: 'dist/',
    devtoolModuleFilenameTemplate: '[absolute-resource-path]'
  },
  resolve: {
    extensions: ['.js', '.json'],
    alias: {
      '@': resolve('src'),
    },
  },
  resolveLoader: {
    modules: [resolve('node_modules')],
  },
  plugins: [
    new webpack.DefinePlugin({
      'process.env': process.env
    }),
    {
      apply: (compiler) => {
        compiler.hooks.afterEmit.tap('AfterEmitPlugin', (compilation) => {
          const packageJson = JSON.parse(fs.readFileSync(path.join(__dirname, '../package.json')).toString());
          const generatedPackageJson = {
            name: packageJson.name,
            apiConfig: packageJson.apiConfig,
            dependencies: packageJson.dependencies,
            description: packageJson.description,
            repository: '',
            license: packageJson.license,
          };
          if (compilation.options.mode === 'production') {
            Object.assign(generatedPackageJson.apiConfig, {
              "log": {
                "level" : "WARNING"
              },
            })
          }
          fs.writeFileSync(path.join(compilation.options.output.path, 'package.json'), JSON.stringify(generatedPackageJson, null, 2));
        });
      }
    }
  ],
  module: {
    rules: [
      {
        use: 'babel-loader',
        exclude: /(node_modules)/,
        test: /\.js$/
      }
    ]
  }
};