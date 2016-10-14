<?php namespace Ecfectus\Framework\Config;

use ArrayAccess;
use Ecfectus\Dotable\DotableInterface;
use Ecfectus\Dotable\DotableTrait;
use JsonSerializable;

class Repository implements ArrayAccess, JsonSerializable, DotableInterface, RepositoryInterface
{
    use DotableTrait;
}
