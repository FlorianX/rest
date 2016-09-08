<?php
/*
*  Copyright notice
*
*  (c) 2016 Daniel Corn <info@cundd.net>, cundd
*
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 3 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
*/

namespace Cundd\Rest\Authentication;

interface AuthenticationProviderInterface
{
    /**
     * Tries to authenticate the current request
     *
     * @return bool Returns if the authentication was successful
     */
    public function authenticate();

    /**
     * Sets the request to get the authentication requirements for
     *
     * @param \Bullet\Request|\Cundd\Rest\Request $request
     * @return mixed
     */
    public function setRequest(\Cundd\Rest\Request $request);
}
