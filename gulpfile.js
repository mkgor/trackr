var gulp = require("gulp");
var sass = require("gulp-sass"), // переводит SASS в CSS
    cssnano = require("gulp-cssnano"), // Минимизация CSS
    autoprefixer = require('gulp-autoprefixer'), // Проставлет вендорные префиксы в CSS для поддержки старых браузеров
    imagemin = require('gulp-imagemin'), // Сжатие изображений
    concat = require("gulp-concat"), // Объединение файлов - конкатенация
    uglify = require("gulp-uglify"), // Минимизация javascript
    rename = require("gulp-rename"); // Переименование файлов

gulp.task("sass", function() {
    return gulp.src("assets/sass/*.scss")
        .pipe(concat('styles.scss'))
        .pipe(sass())
        .pipe(autoprefixer({
            overrideBrowserslist: ['last 7 versions'],
            cascade: false
        }))
        .pipe(cssnano())
        .pipe(rename({ suffix: '.min' }))
        .pipe(gulp.dest("public/dist/css"));
});

// Объединение и сжатие JS-файлов
gulp.task("scripts", function() {
    return gulp.src("assets/js/*.js") // директория откуда брать исходники
        .pipe(concat('bundle.js')) // объеденим все js-файлы в один
        .pipe(uglify()) // вызов плагина uglify - сжатие кода
        .pipe(rename({ suffix: '.min' })) // вызов плагина rename - переименование файла с приставкой .min
        .pipe(gulp.dest("public/dist/js")); // директория продакшена, т.е. куда сложить готовый файл
});

// Сжимаем картинки
gulp.task('imgs', function() {
    return gulp.src("assets/images/*.+(jpg|jpeg|png|gif)")
        .pipe(imagemin({
            progressive: true,
            svgoPlugins: [{ removeViewBox: false }],
            interlaced: true
        }))
        .pipe(gulp.dest("public/dist/images"))
});


///// Таски ///////////////////////////////////////

// Запуск тасков по умолчанию
gulp.task('default', gulp.parallel('sass', 'scripts'));
