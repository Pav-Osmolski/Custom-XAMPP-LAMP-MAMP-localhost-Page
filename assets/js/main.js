// assets/js/main.js
import '../scss/style.scss';

import { initClock } from './modules/clock.js';
import { setColumnWidth, cycleColumnWidth, initColumnFeatures } from './modules/columns.js';
import { initDockConfig } from './modules/dock.js';
import { initApacheErrorLog } from './modules/error.js';
import { initSearch } from './modules/search.js';
import { initSystemMonitoring } from './modules/system.js';
import { initThemeToggle } from './modules/theme.js';
import { initViewToggles } from './modules/view.js';

// Initialise on page load
initClock();
initColumnFeatures();
initDockConfig();
initApacheErrorLog();
initSearch();
initSystemMonitoring();
initThemeToggle();
initViewToggles();
