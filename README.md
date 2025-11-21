# WP Rocket - Support Diagnoser

Repository of the WP Rocket Diagnoser plugin used by the Support team

## Contributions

### Plugin's version

Whenever you make changes to the plugin and before pushing the final code of the new contribution to the repository, make sure to update the version in the `package.json` file.

Only change the version in the `package.json` file.

**IMPORTANT:** Do not touch any line related to the version in `wpr-diagnoser.php` file, it is `x.x.x` because it will be replaced later in the build:release process.

We use the SEMVER v2.0.0 specification here, please check [https://semver.org/](https://semver.org/)

In summary given a version number MAJOR.MINOR.PATCH, increment the:

- **MAJOR** version when you make incompatible API changes
- **MINOR** version when you add functionality in a backward compatible manner
- **PATCH** version when you make backward compatible bug fixes

This helps the UIs that reads the information collected by this plugin to know which version is installed on the site, so, the UIs can know how to read the information (In case something changes from version to version, something that we should avoid whenever possible).

This will be used by the UIs as the "[API version](https://www.postman.com/api-platform/api-versioning/)".

### Building the release

**IMPORTANT:** To use the command to build the release automatically, make sure you have Nodejs installed (version 20.x.x or later) and make sure to be in MacOS or Linux (If you are on Windows, you can use the WSL terminal)

Creating the ZIP file manually can become a tedious process, so, this project includes one command you can run in your terminal.

When you have the final version and want to create a zipped plugin, just run this in your terminal (Make sure you are in the directory of the project):

```npm run build:release```

or if you have node 22.x.x+ installed, you can use the following as well:

```node --run build:release```

If everything goes well, you will have a `wpr-diagnoser-vx.x.x.zip` in the release directory (where x.x.x will be the version in the package.json)

You will have a wpr-diagnoser directory (with the content the zip file will have too) and the version automatically replaced in the `wpr-diagnoser.php` file.

This directory and the zip file are ready to be used in a WordPress site.

**IMPORTANT:** Make sure to update the version in the `package.json` when needed.

### Avoid making changes to WordPress and plugins if they are not needed

This plugin is intended to be used for diagnosis, collecting information that can be helpful to debug issues with WP Rocket, so, changing options in WP Rocket, WordPress or other plugins should be avoided as much as we can.

Only make changes **when it makes 100% sense**, for example the QueryString features modifies the list of QueryStrings allowed by WP Rocket to be cached, but having the feature of disabling and enabling options using QueryStrings makes sense in this context.

Avoiding changes we will reduce the chances of this plugin breaking sites.

### Implementing new features

When implementing new features, please try to keep the new code in a namespace whenever possible, so the new code cannot be accessed from the global scope.

Also, **please use classes to write the new feature** whenever possible to keep the code organized.

### Contribute adding new QueryStrings to enable or disable options

For this you have to go to the file `inc/query-strings.php`, there look for the function called `get_option_list`.

Once there, just add a new entry to the Array using internal name of the option in WP Rocket's code base.

You can add a new entry like this:

```php
    $option_list = [
        //...
        'minify_img' // Assuming the new option is called 'minify_img' in WP Rocket's code base
    ];
```

This will be added automatically to the cached QueryStrings list in WP Rocket.

You can use it now in the URL of the site this plugin is installed: `https://my-domain.com?minify_img=0` or `https://my-domain.com?minify_img=1`

In summary, this doesn't require any implementation from your end, you just have to add a new entry to the array 😊

### Contribute adding new QueryStrings for new features

If you want to have a new QueryString that will be linked to a new feature, you can go to `inc/query-strings.php` and then look for `get_other_query_strings` function.

There just add the new QueryString in the array, just add a new string entry.

For example:

```php
    $other_cached_query_strings = [
        //..
        'my_new_query_string'
    ];
```

The QueryString will be added automatically to the cached QueryString in WP Rocket.

You can use it now in the URL of the site this plugin is installed: `https://my-domain.com?my_new_query_string`

**IMPORTANT:** This only adds the QueryString to the cached QueryStrings in WP Rocket, so, you have to implement the new feature by checking:

```php
if(isset($_GET['my_new_query_string'])) {
    //Run the code
}
```

You might want to check the value of the QueryString too, in case the behavior changes depending on the value.

```php
if($_GET['my_new_query_string'] === 'Some value') {
    //Run the code
} else if($_GET['my_new_query_string'] === 'Some other value') {
    //Run another code
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

## Author

Sandy Figueroa: [Github](https://github.com/sandyfzu)
