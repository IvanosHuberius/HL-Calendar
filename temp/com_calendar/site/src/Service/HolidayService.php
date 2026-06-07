<?php
/**
 * @license GNU/GPL v2 or later
 * @copyright (c) 2026 huberlabs.ch
 */

namespace Jewe\Component\Calendar\Site\Service;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Http\HttpFactory;

/**
 * Fetches and caches public (statutory) holidays from the free OpenHolidays API
 * (https://openholidaysapi.org). Results are stored in #__calendar_holidays so the
 * API is queried at most once per country/subdivision/year, and the calendar keeps
 * working offline afterwards.
 */
class HolidayService
{
    private const API_URL = 'https://openholidaysapi.org/PublicHolidays';

    /** Re-fetch cached data only after this many days. */
    private const REFRESH_DAYS = 180;

    /**
     * Return an array of 'Y-m-d' holiday date strings for the given scope/year.
     */
    public function getHolidays(string $country, string $subdivision, int $year): array
    {
        $country     = strtoupper(substr(trim($country), 0, 2));
        $subdivision = strtoupper(trim($subdivision));

        if ($country === '' || $year < 2000 || $year > 2100) {
            return [];
        }

        $db = Factory::getContainer()->get('DatabaseDriver');

        // 1) Try cache
        $query = $db->getQuery(true)
            ->select($db->quoteName(['dates', 'fetched']))
            ->from($db->quoteName('#__calendar_holidays'))
            ->where($db->quoteName('country') . ' = ' . $db->quote($country))
            ->where($db->quoteName('subdivision') . ' = ' . $db->quote($subdivision))
            ->where($db->quoteName('hyear') . ' = ' . (int) $year);
        $db->setQuery($query);
        $row = $db->loadObject();

        if ($row && $row->fetched) {
            $age = time() - strtotime($row->fetched);
            if ($age >= 0 && $age < self::REFRESH_DAYS * 86400) {
                return $row->dates ? explode(',', $row->dates) : [];
            }
        }

        // 2) Fetch from API
        try {
            $dates = $this->fetchFromApi($country, $subdivision, $year);
            $this->storeCache($db, $country, $subdivision, $year, $dates);
            return $dates;
        } catch (\Throwable $e) {
            // 3) Fall back to (stale) cache if available; otherwise skip nothing
            if ($row && $row->dates !== null) {
                return $row->dates ? explode(',', $row->dates) : [];
            }
            return [];
        }
    }

    /**
     * Query the OpenHolidays API and return a sorted list of 'Y-m-d' strings.
     */
    private function fetchFromApi(string $country, string $subdivision, int $year): array
    {
        $url = self::API_URL
            . '?countryIsoCode=' . urlencode($country)
            . '&validFrom=' . $year . '-01-01'
            . '&validTo=' . $year . '-12-31';

        $http     = HttpFactory::getHttp(['timeout' => 8]);
        $response = $http->get($url, ['Accept' => 'application/json']);

        if ((int) $response->code !== 200 || empty($response->body)) {
            throw new \RuntimeException('Holiday API HTTP ' . $response->code);
        }

        $data = json_decode($response->body, true);
        if (!is_array($data)) {
            throw new \RuntimeException('Holiday API returned invalid JSON');
        }

        $dates = [];
        foreach ($data as $h) {
            // Only statutory public holidays ("like a Sunday")
            if (($h['type'] ?? 'Public') !== 'Public') {
                continue;
            }

            $matches = !empty($h['nationwide']);

            if (!$matches && $subdivision !== '' && !empty($h['subdivisions']) && is_array($h['subdivisions'])) {
                foreach ($h['subdivisions'] as $sub) {
                    if (strtoupper($sub['code'] ?? '') === $subdivision) {
                        $matches = true;
                        break;
                    }
                }
            }

            if (!$matches) {
                continue;
            }

            $startStr = $h['startDate'] ?? null;
            if (!$startStr) {
                continue;
            }
            $endStr = $h['endDate'] ?? $startStr;

            try {
                $d    = new \DateTime($startStr);
                $last = new \DateTime($endStr ?: $startStr);
            } catch (\Exception $ex) {
                continue;
            }

            // Expand multi-day holidays (usually a single day)
            $guard = 0;
            while ($d <= $last && $guard < 60) {
                $dates[$d->format('Y-m-d')] = true;
                $d->modify('+1 day');
                $guard++;
            }
        }

        $list = array_keys($dates);
        sort($list);

        return $list;
    }

    /**
     * Upsert the fetched dates into the cache table.
     */
    private function storeCache($db, string $country, string $subdivision, int $year, array $dates): void
    {
        $datesStr = implode(',', $dates);
        $now      = Factory::getDate()->toSql();

        $del = $db->getQuery(true)
            ->delete($db->quoteName('#__calendar_holidays'))
            ->where($db->quoteName('country') . ' = ' . $db->quote($country))
            ->where($db->quoteName('subdivision') . ' = ' . $db->quote($subdivision))
            ->where($db->quoteName('hyear') . ' = ' . (int) $year);
        $db->setQuery($del);
        try {
            $db->execute();
        } catch (\Throwable $e) {
            // ignore
        }

        $columns = $db->quoteName(['country', 'subdivision', 'hyear', 'dates', 'fetched']);
        $values  = $db->quote($country) . ', '
            . $db->quote($subdivision) . ', '
            . (int) $year . ', '
            . $db->quote($datesStr) . ', '
            . $db->quote($now);

        $ins = $db->getQuery(true)
            ->insert($db->quoteName('#__calendar_holidays'))
            ->columns($columns)
            ->values($values);
        $db->setQuery($ins);
        try {
            $db->execute();
        } catch (\Throwable $e) {
            // ignore
        }
    }
}
