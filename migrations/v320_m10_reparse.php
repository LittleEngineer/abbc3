<?php
/**
*
* Advanced BBCode Box
*
* @copyright (c) 2018 Matt Friedman
* @license GNU General Public License, version 2 (GPL-2.0)
*
*/

namespace vse\abbc3\migrations;

use phpbb\db\migration\container_aware_migration;

class v320_m10_reparse extends container_aware_migration
{
	/**
	 * {@inheritdoc}
	 */
	public static function depends_on()
	{
		return array(
			'\vse\abbc3\migrations\v320_m9_delete_bbcodes',
			'\phpbb\db\migration\data\v320\text_reparser',
		);
	}

	/**
	 * {@inheritdoc}
	 */
	public function effectively_installed()
	{
		return $this->config->offsetExists('abbc3_reparse');
	}

	/**
	 * {@inheritdoc}
	 */
	public function update_data()
	{
		return array(
			array('config.add', array('abbc3_reparse', time())),
			array('if', array(
				$this->config->offsetExists('reparse_lock'),
				array('config.remove', array('reparse_lock', 0)),
			)),
			array('if', array(
				!$this->config->offsetExists('text_reparser.pm_text_last_cron'),
				array('config.add', array('text_reparser.pm_text_last_cron', 0)),
			)),
			array('if', array(
				!$this->config->offsetExists('text_reparser.pm_text_cron_interval'),
				array('config.add', array('text_reparser.pm_text_cron_interval', 10)),
			)),
			array('if', array(
				!$this->config->offsetExists('text_reparser.post_text_last_cron'),
				array('config.add', array('text_reparser.post_text_last_cron', 0)),
			)),
			array('if', array(
				!$this->config->offsetExists('text_reparser.post_text_cron_interval'),
				array('config.add', array('text_reparser.post_text_cron_interval', 10)),
			)),
			array('custom', array(array($this, 'reset_reparsers'))),
		);
	}

	/**
	 * Initialize post/pm reparsers
	 */
	public function reset_reparsers()
	{
		/** @var \phpbb\textreparser\manager $reparser_manager */
		$reparser_manager = $this->container->get('text_reparser.manager');

		/** @var \phpbb\di\service_collection $reparsers */
		$reparsers = $this->container->get('text_reparser_collection');

		/**
		 * @var string $name
		 * @var \phpbb\textreparser\reparser_interface $reparser
		 */
		foreach ($reparsers as $name => $reparser)
		{
			if (in_array($name, ['text_reparser.post_text', 'text_reparser.pm_text']))
			{
				$reparser_manager->update_resume_data($name, 1, $reparser->get_max_id(), 100);
			}
		}
	}
}
