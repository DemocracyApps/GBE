<?php namespace DemocracyApps\GB\Organizations;

/**
 *
 * This file is part of the Government Budget Explorer (GBE).
 *
 *  The GBE is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GBE is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with the GBE.  If not, see <http://www.gnu.org/licenses/>.
 */

use DemocracyApps\GB\Utility\EloquentPropertiedObject;
use DemocracyApps\MemberOrg\EloquentOrganizationMember;
use DemocracyApps\MemberOrg\OrganizationMember;

class MediaOrganizationUser extends EloquentPropertiedObject implements OrganizationMember {

    use EloquentOrganizationMember;
}