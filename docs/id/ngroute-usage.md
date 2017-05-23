Untuk menggunakan angular route, kita memakai widget `dee\angularjs\NgRoute`.
```php
<div ng-app="myApp">
    <?= NgRoute::widget([
        'name' => 'myApp',
        'html5Mode' => true,
        'baseUrl' => 'myapp/',
        'routes' => 
            '/' => [
                'templateFile' => 'templates/main.php',
                'controllerFile' => 'controllers/main.js',
            ],
            '/view/:id' => [
                'templateFile' => 'templates/view.php',
                'controllerFile' => 'controllers/view.js',
            ],
            '/edit/:id' => [
                'templateFile' => 'templates/edit.php',
                'controllerFile' => 'controllers/edit.js',
            ],
            '*' => [ // otherwise
                'redirectTo' => '/',
            ],
        ]
    ])?>
</div>
```

Setelah itu kita definisikan `view` dan `controller` untuk masing-masing route.

Jika `html5Mode` diaktifkan, kita juga harus menset nilai dari `baseUrl`. Nilai `baseUrl` ini
disesuaikan dengan url rules dari `urlManager`

```php
    'urlManager' => [
        'rules' => [
            'myapp' => 'my-controller/index',
            'myapp/<_x:.*>' => 'my-controller/index',
        ]
    ]
```
