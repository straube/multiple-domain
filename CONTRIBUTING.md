# Contributing to Multiple Domain

I'm glad you got here and are looking on how to contribute to **Multiple Domain**.

You can help:

* improving the docs (right now we only have a list of FAQs in the `README.me` file);
* answering questions posted both in the [GitHub](https://github.com/straube/multiple-domain/issues) page
    and in the [WordPress Forum](https://wordpress.org/support/plugin/multiple-domain/);
* writing new features, fixing known bugs or [testing](#testing);
* [donating](#donations).

Feel free to open a pull request to address any of the issues reported by the plugin users. In case you have questions
on how to fix or the best approach, start a discussion on the appropriate thread.

If you want to add a new feature, please open an issue explaining the feature and how it would help the users before
start writing your code.

Separate each new feature or improvement into a separate branch in your forked repository.

## Guidelines

To make sure every contribution follows the same code style, please follow these rules:

* Write PSR-2 compliant code
* Use UNIX line returns
* Remove trailing white space
* Use 4 spaces instead of tabs

Also notice that even if Wordpress has its own code styling guidelines, this plugin doesn't follow it in favor of a
global standard (PSR-2).

## Testing

Before running the tests, you may have to prepare the environment. First, install the requirements:

```
$ composer install
```

In case you don't have Composer installed, follow the [instructions](https://getcomposer.org/doc/00-intro.md) to
install it.

Then, install the WordPress test lib and the testing database:

```
$ bash bin/install-wp-tests.sh multiple_domain_test root '' localhost latest
```

To run the command above you need PHP, MySQL and SVN installed in your local env. It'll create a MySQL database named
`multiple_domain_test`.

If you have any trouble or need more details on what are the options, please refer to the official docs on how to
[initialize the testing environment locally](https://make.wordpress.org/cli/handbook/plugin-unit-tests/#running-tests-locally).

Finally, to run the tests, call the PHPUnit program:

```
$ vendor/bin/phpunit
```

## Donations

If you find this plugin helpful, you can support the work involved buying me a coffee, a beer or a Playstation 4 game.
You can send donations over PayPal to gustavo.straube@gmail.com.
