<?php namespace Hansvn\Icepay\Facades;

use Illuminate\Support\Facades\Facade;

class Icepay extends Facade {
	protected static function getFacadeAccessor() {
		return 'icepay';
	}
}