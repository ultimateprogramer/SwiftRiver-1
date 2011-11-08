<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Model for Accounts
 *
 * PHP version 5
 * LICENSE: This source file is subject to GPLv3 license 
 * that is available through the world-wide-web at the following URI:
 * http://www.gnu.org/copyleft/gpl.html
 * @author     Ushahidi Team <team@ushahidi.com> 
 * @package    Ushahidi - http://source.swiftly.org
 * @category Models
 * @copyright  Ushahidi - http://www.ushahidi.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License v3 (GPLv3) 
 */
class Model_Account extends ORM
{
	/**
	 * An account has many rivers, buckets, snapshots, sources
	 *
	 * An account has and belongs to many droplet_links,
	 * droplet_places, droplet_tags, droplet_attachments
	 * and plugins
	 *
	 * @var array Relationships
	 */
	protected $_has_many = array(
		'rivers' => array(),
		'buckets' => array(),
		'snapshots' => array(),
		'sources' => array(),
		'droplets_attachments' => array(
			'model' => 'droplets_attachment',
			'through' => 'accounts_droplets_attachments'
			),
		'droplets_links' => array(
			'model' => 'droplets_link',
			'through' => 'accounts_droplets_links'
			),
		'droplets_tags' => array(
			'model' => 'droplets_tag',
			'through' => 'accounts_droplets_tags'
			),
		'droplets_places' => array(
			'model' => 'droplets_place',
			'through' => 'accounts_droplets_places'
			),
		'plugins' => array(
			'model' => 'plugin',
			'through' => 'accounts_plugins'
			)			
		);		
	
	/**
	 * An account belongs to a user
	 *
	 * @var array Relationhips
	 */
	protected $_belongs_to = array('user' => array());

	/**
	 * Overload saving to perform additional functions on the account
	 */
	public function save(Validation $validation = NULL)
	{

		// Do this for first time items only
		if ($this->loaded() === FALSE)
		{
			// Save the original creator of this account
			// Logged In User
			$user = Auth::instance()->get_user();
			if ($user)
			{
				$this->user_id = $user->id;
			}

			// Save the date the feed was first added
			$this->account_date_add = date("Y-m-d H:i:s", time());
		}
		else
		{
			$this->account_date_modified = date("Y-m-d H:i:s", time());
		}

		return parent::save();
	}	
}