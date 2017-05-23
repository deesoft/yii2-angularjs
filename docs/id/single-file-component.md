Untuk mendefinisikan `component` atau `route`, kita bisa menggabungkan antara `view` dengan `controller` dalam satu file.
```php
<div ng-app="angularYii">
    <?php Module::begin([
        'name' => 'angularYii', // module name, use for ng-app
        'components' => [
            'mainView' => [
                'templateFile' => 'templates/main-view.php',
                'controllerAs' => '$ctrl', // default
            ]
        ]
    ])?>
        <main-view></main-view>
    <?php Module::end()?>
</div>
```
file `templates/main-view.php`
```php
<template>
    <div>
        <ul>
            <li ng-repeat="todo in $ctrl.todos">{{todo.name}}</li>
        </ul>
        <input ng-model="$ctrl.newValue"><button ng-click="$ctrl.addTodo()">Add</button>
    </div>
</template>
<script>
    function(){
        var $ctrl = this;
        
        $ctrl.todos = [];
        $ctrl.addTodo = function(){
            // ...
        }
    }
</script>
```

Yang harus diperhatikan adalah, function controller didefinisikan di dalam tag `script`. Tidak boleh ada apapun dalam tag `script` selain definisi function.