# wp-rocket-diagnoser
Repository of the WP Rocket Diagnoser plugin used by the Support team

## Contributions

### Avoid making changes to WordPress and plugins if they are not needed

This plugin is intended to be used for diagnosis, collecting information that can be helpful to debug issues with WP Rocket, so, changing options in WP Rocket, WordPress or other plugins should be avoided as much as we can.

Only make changes **when it makes 100% sense**, for example the QueryString features modifies the list of QueryStrings allowed by WP Rocket to be cached, but having the feature of disabling and enabling options using QueryStrings makes sense in this context.

Avoiding changes we will reduce the chances of this plugin breaking sites.

### Implementing new features

When implementing new features, please try to keep the new code in a namespace whenever possible, so the new code cannot be accessed from the global scope.

Also, **please use classes to write the new feature** whenever possible to keep the code organized.

### Contribute adding new QueryStrings to enable or disable options

For this you have to go to the file `inc/query-strings.php`, there look for the function called `get_option_list`.

Once there, just add a new entry to the Array.

For example, let's say that a new option called `minify_img` (internal WP Rocket option name) is added to WP Rocket and you want to cover it in this plugin.

You can add a new entry like this:

```php
    $option_list = [
        //...
        'minifyimg' => 'minify_img'
    ];
```

In which the key `minifyimg` is the name that will be used to construct the QueryStrings, and the value `minify_img` is the name of the option in WP Rocket (The string WP Rocket uses to get and save the option in the database).

`minifyimg` will be automatically prefixed with `wpr-no-` for the new option deactivation and `wpr-activate-` for the new option activation, so, by only adding that to the array, two new QueryStrings will be created for you:

* wpr-no-minifyimg
* wpr-activate-minifyimg

And these will be added automatically to the cached QueryStrings list in WP Rocket.

You can use it now in the URL of the site this plugin is installed: `https://my-domain.com?wpr-no-minifyimg` or `https://my-domain.com?wpr-activate-minifyimg`

In summary, this doesn't require any implementation from your end, you just have to add a new entry to the array 😊

### Contribute adding new QueryStrings for new features

If you want to have a new QueryString that will be linked to a new feature, you can go to `inc/query-strings.php` and then look for `get_other_cache_query_strings` function.

There just add the new QueryString in the array, just add a new string entry.

For example:

```php
    $other_cached_query_strings = [
        //..
        'my_new_query_string'
    ];
```

This QueryString **won't be prefixed** so, whatever you add to the array will be used as it is as a QueryString, for example ?my_new_query_string.

The QueryString will be added automatically to the cached QueryString in WP Rocket.

You can use it now in the URL of the site this plugin is installed: `https://my-domain.com?my_new_query_string`

**IMPORTANT:** This only adds the QueryString to the cached QueryStrings in WP Rocket, so, you have to implement the new feature by checking:

```php
if(isset($_GET['my_new_query_string'])) {
    //Run the code
}
```

### Contribute adding new options or filters for the diagnoser to collect information of it

In this case, let's say that a new option is added to the WP Rocket, and we want the diagnoser to collect information from the new option and it's filters.

If the option is an optimization (something that it is applied normaly by the `rocket_buffer` filter), you can go to the file `inc/WPRDiagnoserOptions.php` and locate the `$options` array. There you will find the documentation about how to add new options and it's related filters.

But if the option is not an optimization as such, but an option like for example Preload, Mobile cache, etc, you can go to the file `inc/WPRDiagnoser.php` and locate the array `$no_rocket_options` and just add a new string to the array with the name of the option.

For filters, locate the array called `$no_rocket_filters`, and add a new entry `'key' => VALUE`, and make sure to provide the name of the filer as the key and the default value of the filter as the value in the array (You can check the default value in WP Rocket's code).

Default values are important to prevent this plugin from breaking sites by passing wrong values to the filter.

### Contribute by adding new information that doesn't fit in the other sections

Go to `inc/WPRDiagnoser.php`, there you can decide if you need to add a new method in the class or need to create a new class in a separated file, or if you simply need to modify an existent method to add an extra information. For example, adding a new `server information` in the `getServerInformation` method.

At the end, just make sure to create a new entry in the `$result` array in the `set_no_rocket_print_on_footer` method wherever you think it fits.

If you are adding the information in an existent method, then just add the new entry to the local `$result` array of that method and don't do anything in the `$result` array in `set_no_rocket_print_on_footer`.

**IMPORTANT:** Make sure to catch errors when trying to retrieve information, and return `null` or `"Couldn't get"` if you cannot get that information, so, errors are not logged in customer's websites log files.
