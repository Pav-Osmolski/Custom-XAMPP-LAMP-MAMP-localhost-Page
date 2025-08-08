// assets/js/main.js
import '../scss/style.scss';

import {toggleApache} from './modules/apache.js';
import {initClock} from './modules/clock.js';
import {setColumnWidth, cycleColumnWidth, initColumnFeatures} from './modules/columns.js';
import {initDockConfig} from './modules/dock.js';
import {initApacheErrorLog, initPhpErrorLog} from './modules/error.js';
import {initFoldersConfig} from './modules/folders.js';
import {initLinkTemplates} from './modules/linkTemplates.js';
import {initSearch} from './modules/search.js';
import {initClearStorageButton} from './modules/settings.js';
import {autoHideConfirmationMessage} from './modules/settings.js';
import {initSystemMonitoring} from './modules/system.js';
import {initThemeSwitcher} from './modules/theme.js';
import {setupVhostCertButtons} from './modules/vhosts.js';
import {initToggleAccordion} from './modules/view.js';
import {initViewToggles} from './modules/view.js';

// Initialise on page load
initClock();
initColumnFeatures();
initDockConfig();
initFoldersConfig();
initLinkTemplates();
initApacheErrorLog();
initPhpErrorLog();
initSearch();
initClearStorageButton();
autoHideConfirmationMessage();
initSystemMonitoring();
initThemeSwitcher();
setupVhostCertButtons();
initToggleAccordion();
initViewToggles();
