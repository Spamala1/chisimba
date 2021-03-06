<?php
/**
*
* Colorbox inserts text into coloured boxes
*
* Colorbox inserts text into coloured boxes, for example as used in
* the colorbox filter
*
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
* @package   sdfasad
* @author    Administrative User <admin@localhost.local>
* @copyright 2007 AVOIR
* @license   http://www.gnu.org/licenses/gpl-2.0.txt The GNU General Public License
* @version   $Id$
* @link      http://avoir.uwc.ac.za
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
* Colorbox inserts text into coloured boxes
*
* Colorbox inserts text into coloured boxes, for example as used in
* the colorbox filter
*
* @category  Chisimba
* @author Derek Keats <derek@dkeats.com>
* @author Paul Scott <pscott@uwc.ac.za>
* @copyright UWC and AVOIR under the GPL
* @package   utilities
* @copyright 2007 AVOIR
* @license   http://www.gnu.org/licenses/gpl-2.0.txt The GNU General
Public License
* @version   $Id$
* @link      http://avoir.uwc.ac.za
*/
class colorbox extends object
{


    /**
     * Constructor method. It does nothing here
     *
     * @access public
     * @return VOID
     *
     */
    public function init()
    {
    }

    /**
     *
     * Render the colour box
     * 
     * @param string $boxColor The name corrensponding to the box colour in the stylesheet
     * @param string $txt The contents of the div box
     * @return string The rendered DIV
     * @access public
     * 
     */
    public function show($boxColor, $txt)
    {
        return "<div class=\"colorbox $boxColor\">$txt</div>";
    }
}
?>