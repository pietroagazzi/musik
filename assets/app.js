// any CSS you import will output into a single css file (app.css in this case)
import './styles/app.css';

import renderAlerts from './toastify';
import {squares} from './animations';
// start the Stimulus application
import './bootstrap';

// Toastify alerts
renderAlerts(alerts);

// Squares animation
squares();
