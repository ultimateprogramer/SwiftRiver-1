<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Model for Droplets_Attachments
 *
 * PHP version 5
 * LICENSE: This source file is subject to GPLv3 license 
 * that is available through the world-wide-web at the following URI:
 * http://www.gnu.org/copyleft/gpl.html
 * @author     Ushahidi Team <team@ushahidi.com> 
 * @package    Ushahidi - http://source.swiftly.org
 * @category   Models
 * @copyright  Ushahidi - http://www.ushahidi.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License v3 (GPLv3) 
 */
class Model_Droplets_Attachment extends ORM
{
	/**
	 * A droplet has and belongs to many accounts
	 *
	 * @var array Relationships
	 */
	protected $_has_many = array(
		'accounts' => array(
			'model' => 'account',
			'through' => 'accounts_droplets_attachments'
		));
}