Dependency Injection
===============

Dua cara yang diperkenankan untuk dependency injection
```js
// cara 1
function ($scope, $route) {

}

// dan cara 2
['$scope', '$route', function ($scope, $route) {

}]
```

Misal, untuk membuat filter `nl2br`, kodenya adalah
`index.php`
```php
<?= Module::widget([
    'name' => 'myApp',
    'filters' => [
        'nl2br' => ['sourceFile' => 'js/nl2br.js'],
    ],
]);
?>
```

`js/nl2br.js`
```js
function ($sce) {
    return function (msg) {
        var result = (msg + '').replace(/([^>\r\n]?)(\r\n|\n\r|\r|\n)/g, '$1<br>$2');
        return $sce.trustAsHtml(result);
    }
}
```

Alternatifnya adalah, kita definisikan dalam `preJsFile`.
`index.php`
```php
<?= Module::widget([
    'name' => 'myApp',
    'preJsFile' => 'js/pre.js',
]);
?>
```

`js/pre.js`
```js
myApp.filter('nl2br', ['$sce', function ($sce) {
    return function (msg) {
        var result = (msg + '').replace(/([^>\r\n]?)(\r\n|\n\r|\r|\n)/g, '$1<br>$2');
        return $sce.trustAsHtml(result);
    }
}]);
```
