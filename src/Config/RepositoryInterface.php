<?php
/**
 * Created by PhpStorm.
 * User: leemason
 * Date: 08/04/16
 * Time: 13:46
 */

namespace Ecfectus\Config;

/**
 * Representation of a configuration component.
 *
 * The repository should provide the methods detailed below.
 */
interface RepositoryInterface
{
    /**
     * Set the environment the repository should build its configuration from
     *
     * Internally this should defer to the implementation on how environments are stored.
     *
     * I suggest environments should cascade from a core definition and replace core definitions with environment specific values.
     * In a file/folder based implementation this would be represented as:
     *
     * /config/app.php
     * /config/production/app.php - values found here, replace values found in the above file.
     */
    public function setEnvironment($environment);

    /**
     * Sets the configuration value within the repository.
     *
     * The $key must be a string, dot notation is advised for namespacing items, how this is handled internally is up to the implementation.
     *
     * I suggest internally this is stored as an array. As this allows for returning an array group of items.
     *
     * @param $key
     * @param $value
     * @return mixed
     */
    public function set($key, $value);

    /**
     * Checks if the configuration repository has the item specified by $key
     *
     * @param $key
     * @return bool
     */
    public function has($key);

    /**
     * Returns an item from the repository by $key.
     *
     * Optionally provide a default value for when its not found.
     *
     * @param $key
     * @param null $default
     * @return mixed
     */
    public function get($key, $default = null);
}