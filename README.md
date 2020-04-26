# Filmmakers for Future plugin
The Filmmakers for Future plugin is a handler for [Urlaube](https://github.com/urlaube/urlaube). It is based on the [fff-signup](https://github.com/yahesh/fff-signup) prototype maintained by [Yahe](https://github.com/yahesh).

## Installation
Place the folder containing the plugin into your handlers directory located at ./user/plugins/.

For further installation steps see the [README.md](https://github.com/yahesh/fff-signup/blob/master/README.md) of [fff-signup](https://github.com/yahesh/fff-signup).

## Configuration
To configure the plugin you can change the corresponding settings in your configuration file located at `./user/config/config.php`.

### Videos
You can configure the lists of videos that is used by the `[fm4fvideos $listname]` shortcode:
```
Plugins::preset("VIDEOS", null);
```

You have to provide an array of arrays that has the following format:
```
["$listname_1" => [["category" => "$category_1_1",
                    "hoster"   => "$hoster_1_1",
                    "language" => "$language_1_1",
                    "name"     => "$name_1_1",
                    "thumb"    => "$thumb_1_1",
                    "url"      => "$url_1_1"],
                   ["category" => "$category_1_2",
                    "hoster"   => "$hoster_1_2",
                    "language" => "$language_1_2",
                    "name"     => "$name_1_2",
                    "thumb"    => "$thumb_1_2",
                    "url"      => "$url_1_2"]],
 "$listname_2" => [["category" => "$category_2_1",
                    "hoster"   => "$hoster_2_1",
                    "language" => "$language_2_1",
                    "name"     => "$name_2_1",
                    "thumb"    => "$thumb_2_1",
                    "url"      => "$url_2_1"],
                   ["category" => "$category_2_2",
                    "hoster"   => "$hoster_2_2",
                    "language" => "$language_2_2",
                    "name"     => "$name_2_2",
                    "thumb"    => "$thumb_2_2",
                    "url"      => "$url_2_2"]]]
```

### Further Configuration
For further configuration steps see the [config.php.example](https://github.com/yahesh/fff-signup/blob/master/config/config.php.example) of [fff-signup](https://github.com/yahesh/fff-signup).

**Take note:** Instead of using `define()` to set the configuration values you **should** use `Plugins::set()`.
