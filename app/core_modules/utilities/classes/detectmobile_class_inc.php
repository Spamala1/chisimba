<?php
/**
 *
 * Wrapper for Mobile Detect
 *
 * A wrapper for Mobile Detect class 
 *
 * PHP version 5
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the
 * Free Software Foundation, Inc.,
 * 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 *
 * @category  Chisimba
 * @package   utilities
 * @author    Derek Keats derek@localhost.local
 * @copyright 2007 AVOIR
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt The GNU General Public License
 * @version   0.001
 * @link      http://www.chisimba.com
 *
 */

// security check - must be included in all scripts
if (!
/**
 * The $GLOBALS is an array used to control access to certain constants.
 * Here it is used to check if the file is opening in engine, if not it
 * stops the file from running.
 *
 * @global entry point $GLOBALS['kewl_entry_point_run']
 * @name   $kewl_entry_point_run
 *
 */
$GLOBALS['kewl_entry_point_run'])
{
        die("You cannot view this page directly");
}
// end security check

/**
*
* Wrapper for Mobile Detect
*
* A wrapper for Mobile Detect class . Use it as follows:
*  $objDetector = $this->newObject('detectmobile', 'utilities');
*  if  ($objDetector->device->isMobile()) {
*     // code for mobile devices
*  }
*
* if ($objDetector->device->isTablet()) {
*    // code for tablet devices
* }
* 
* @package   utilities
* @author    Derek Keats derek@localhost.local
*
*/
class detectmobile extends object
{
    /**
     *
     * @var string object $device The detector object
     * 
     */
    public $device;

    /**
    *
    * Intialiser for the species operations class
    * @access public
    * @return VOID
    *
    */
    public function init()
    {
        require_once($this->getResourceUri('mobiledetect/Mobile_Detect.php', 
           'utilities'));
        $this->device = new Mobile_Detect();
    }
}