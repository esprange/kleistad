# grunt-checkwpversion

Compare your WordPress plugin's 'stable tag' in your readme.txt with the version in the plug-in header and the version your package.json.

Typically in a WordPress plug-in you'll have various version values:
 - Stable tag in the `readme.txt`
 - Version in the plug-in header
 - The version in `package.json` (since you're using Grunt).

This plug-in allows you to add checks to ensure that specified relationships between those versions are met. If not, subsequent tasks will not run unless you use `--force`. Typical use will be ensuring that these versions all agree before deployment.

This is a [Grunt](http://gruntjs.com/) 0.4 plugin. If you haven't used [Grunt](http://gruntjs.com/) before, be sure to
check out the [Getting Started](http://gruntjs.com/getting-started) guide, as it explains how to create a
[Gruntfile](http://gruntjs.com/sample-gruntfile) as well as install and use Grunt plugins.

## Installation

Use npm to install and save the plugin into `devDependencies`.

```shell
npm install grunt-checkwpversion --save-dev
```

Once the plugin has been installed, it may be enabled inside your Gruntfile with this line of JavaScript:

```js
grunt.loadNpmTasks('grunt-checkwpversion');
```

## Configuration

In your project's Gruntfile, add a section named `checkwpversion` to the data object passed into `grunt.initConfig()`. This
is a multitask task and accepts multiple targets.

```js
grunt.initConfig({
		checkwpversion: {
			options:{
				//Options specifying location your plug-in's header and readme.txt
			},
			check: {
				//A check to perform
			},
		}
});
```


## Options

#### readme
Type: `String`  
Default: 'readme.txt'

Location of your plug-in's readme. 

#### readme
Type: `String`  
Default: `[plugin-name].php`

Location of your plug-in's main file (where the header lives). 


## Checks

Checks are almost identical to php's `version_compare`. There is one noticable difference: Unlike `version_compare`, **2.6.0** is equivalent to **2.6** and **2.6.0.0**. Otherwise comparisons of versions are the same.

> The function first replaces `_`, `-` and `+` with a dot `.` in the version strings and also inserts dots `.` before and after any non number so that for example `4.3.2RC1` becomes `4.3.2.RC.1`. Then it splits the results like if you were using `explode('.', $ver)`. Then it compares the parts starting from left to right. If a part contains special version strings these are handled in the following order: `any string not found in this list < dev < alpha = a < beta = b < RC = rc < # < pl = p`. 

Each check must specify the following arguments

### version1
Type: `String`  

This be may given as a hardcoded version, or `readme` for the stable tag stored in `readme.txt` (location specified in options) or `plugin` for the version specified in the plug-in header (again, location specified in the options). You can also use `<%= pkg.version %>` for the version specified in your `package.json`. See below for examples:

### version2
Type: `String`  

Same as above. The second version in the comparison.

### comparison
Type: `String`  

The relationship between the versions that is to be verified. Supported comparisons are 

 - Equal to: `=`, `==`, `eq`
 - Not equal to: `!=`, `<>`, `neq`
 - Greater than or equal to: `>=`, `ge`
 - Less than or equal to: `<=`, `le`
 - Strictly greater than: `>`, `gt`
 - Strictly less than: `<`, `lt`


## Usage Example

Task with all available options:

```js
grunt.initConfig({
	pkg: grunt.file.readJSON('package.json'),
	checkwpversion: {
		options:{
			readme: 'readme.txt',
			plugin: 'my-plugin.php',
		},
		check: { //Check plug-in version and stable tag match
			version1: 'plugin',
			version2: 'readme',
			compare: '==',
			},
		},
		check2: { //Check plug-in version and package.json match
			version1: 'plugin',
			version2: '<%= pkg.version %>',
			compare: '==',
			},
		}
});
```

## Contributing
In lieu of a formal styleguide, take care to maintain the existing coding style. Add unit tests for any new or changed functionality. Lint and test your code using [Grunt](http://gruntjs.com/).

## Release History

### 0.3.0
Fixes bug in searching for version in plug-in header. Add error message to `grunt.fail.warn()`.

### 0.2.0
Include target name in failure message. Set `readme` option to `readme.text` by default.

