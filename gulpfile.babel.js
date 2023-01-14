import gulp from 'gulp';
import { deleteAsync } from 'del';
import gulpSass from 'gulp-sass';
import dartSass from 'sass';
import terser from 'gulp-terser';
import zip from 'gulp-zip';
import autoprefixer from 'gulp-autoprefixer';
import sourcemaps from 'gulp-sourcemaps';
import strip from 'gulp-strip-comments';
import imagemin from 'gulp-imagemin';
import wpPot from 'gulp-wp-pot';
import rtlcss from 'gulp-rtlcss';
import rename from 'gulp-rename';
import babel from 'gulp-babel';
import environments from 'gulp-environments';
import browserSync from 'browser-sync';
import { createRequire } from 'module';

const require = createRequire( import.meta.url );
const pkg = require( './package.json' );
const config = require( './gulp.config.json' );

const { series, parallel, dest, watch, src } = gulp;
const { init, write } = sourcemaps;
const sass = gulpSass( dartSass );

const development = environments.development;
const production = environments.production;
const reload = browserSync.reload;

const CssOutputStyle = development() ? 'expanded' : 'compressed'; // compressed || expanded || compact || nested

const ZIPFILE = pkg.name + '-v' + pkg.version + '.zip';

const LOCALIZATION_OPTIONS = {
	domain: pkg.name,
	package: pkg.name,
	bugReport: pkg.bugs.url,
	lastTranslator: pkg.author.name + ' <' + pkg.author.email + '>',
	team: pkg.author.name + ' <' + pkg.author.email + '>',
};

const frontImg = ( done ) => {
	src( config.paths.images.src )
		.pipe( imagemin() )
		.pipe( dest( config.paths.images.dest ) );

	done();
};

const copyScripts = ( done ) => {
	if ( 0 !== config.paths.scripts.vendor.elements.length ) {
		src( config.paths.scripts.vendor.elements, {
			base: config.paths.scripts.vendor.base,
		} )
			.pipe( production( strip() ) )
			.pipe( production( terser() ) )
			.pipe( dest( config.paths.scripts.dest ) );
	}
	done();
};

const copyStyles = ( done ) => {
	if ( 0 !== config.paths.styles.vendor.elements.length ) {
		src( config.paths.styles.vendor.elements, {
			base: config.paths.styles.vendor.base,
		} ).pipe( dest( config.paths.styles.dest ) );
	}
	done();
};

const copyFonts = ( done ) => {
	src( config.paths.fonts.src ).pipe( dest( config.paths.fonts.dest ) );
	done();
};

const frontStyles = ( done ) => {
	src( config.paths.styles.src )
		.pipe( development( init() ) )
		.pipe(
			sass( {
				outputStyle: CssOutputStyle, // compressed || expanded || compact || nested
			} ).on( 'error', sass.logError )
		)
		.pipe( autoprefixer() )
		.pipe( development( write( './' ) ) )
		.pipe( dest( config.paths.styles.dest ) ) // Output LTR stylesheets.
		.pipe( development( browserSync.stream() ) );

	done();
};

const frontStylesRTL = ( done ) => {
	src( config.paths.styles.src )
		.pipe( development( init() ) )
		.pipe(
			sass( {
				outputStyle: CssOutputStyle, // compressed || expanded || compact || nested
			} ).on( 'error', sass.logError )
		)
		.pipe( autoprefixer() )
		.pipe( rtlcss() )
		.pipe(
			rename( {
				suffix: '-rtl',
			} )
		)
		.pipe( development( write( './' ) ) )
		.pipe( dest( config.paths.styles.dest ) ) // Output RTL stylesheets.
		.pipe( development( browserSync.stream() ) );

	done();
};

const frontScripts = ( done ) => {
	src( config.paths.scripts.src )
		.pipe(
			development(
				init( {
					loadMaps: true,
				} )
			)
		)
		.pipe(
			babel( {
				presets: [ '@babel/env' ],
			} )
		)
		.pipe( production( strip() ) )
		.pipe( production( terser() ) )
		.pipe( development( write( './' ) ) )
		.pipe( dest( config.paths.scripts.dest ) );

	done();
};

const makePot = ( done ) => {
	src( config.paths.php.watch )
		.pipe( wpPot( LOCALIZATION_OPTIONS ) )
		.pipe( dest( config.localization.dist ) );

	done();
};

const release = ( done ) => {
	clean();
	src( config.paths.build.src ).pipe( dest( config.paths.build.dest + pkg.name ) );

	done();
};

const clean = () => {
	return deleteAsync( [ config.paths.build.dest ] );
};

const bundle = ( done ) => {
	src( config.paths.build.dest + '**' )
		.pipe( zip( ZIPFILE ) )
		.pipe( dest( config.paths.build.releases ) );

	done();
};

const scripts = parallel( frontScripts );
const styles = parallel( frontStyles, frontStylesRTL );
const build = series(
	clean,
	parallel( scripts, styles, copyScripts, copyStyles, copyFonts, frontImg ),
	makePot
);

const fileChanges = () => {
	watch( config.paths.styles.watch, styles );
	watch( config.paths.scripts.watch, scripts ).on( 'change', reload );
};

const serve = () => {
	build();
	browserSync.init( {
		files: config.paths.php.watch,
		proxy: config.browserSync.proxy.host,
		https: config.browserSync.ssl,
	} );

	fileChanges();
};

export {
	serve as default,
	fileChanges as watch,
	makePot as pot,
	frontImg as img,
	scripts as js,
	styles as css,
	build,
	release,
	bundle,
	clean,
};
