import datetime as dt
import unittest

import numpy as np

from preparar_dados import load_config, sector_for
from treino import Counts, availability_cutoff, labels_available, rows


class ProtocolTest(unittest.TestCase):
    @classmethod
    def setUpClass(cls):
        cls.config = load_config()
        cls.counts = Counts(cls.config)

    def test_publication_cutoff_across_year_boundary(self):
        self.assertEqual(dt.date(2023, 11, 1), availability_cutoff(dt.date(2024, 1, 1), 2))
        self.assertEqual(dt.date(2026, 8, 1), availability_cutoff(dt.date(2026, 10, 20), 2))

    def test_future_observations_do_not_change_features(self):
        other = Counts(self.config)
        original, _ = self.counts.features(dt.date(2025, 1, 6), 0)
        cutoff = availability_cutoff(dt.date(2025, 1, 6), 2)
        index = (cutoff-other.start).days
        other.daily[index:, :] += 100000
        other.cumulative = np.vstack([np.zeros((1, 4)), np.cumsum(other.daily, axis=0)])
        changed, _ = other.features(dt.date(2025, 1, 6), 0)
        self.assertEqual(original, changed)

    def test_complete_zero_grid_and_labels_known_before_fit(self):
        all_rows = rows(self.counts)
        self.assertTrue(any(r['y'] == 0 for r in all_rows))
        self.assertEqual(4, len({r['sector_id'] for r in all_rows[:4]}))
        cutoff = availability_cutoff(dt.date(2025, 1, 1), 2)
        known = labels_available(all_rows, dt.date(2025, 1, 1), self.config)
        self.assertTrue(all(dt.date.fromisoformat(r['until']) <= cutoff for r in known))
        self.assertFalse(any(r['from'].startswith('2025') for r in known))

    def test_border_has_one_deterministic_sector(self):
        b = self.config['bounds']
        lat, lng = (b['south']+b['north'])/2, (b['west']+b['east'])/2
        self.assertEqual('NE', sector_for(lat, lng, self.config))
        self.assertEqual('SO', sector_for(b['south'], b['west'], self.config))
        self.assertIsNone(sector_for(0, 0, self.config))


if __name__ == '__main__':
    unittest.main()
