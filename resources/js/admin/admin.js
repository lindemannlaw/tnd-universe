import '../bootstrap';

import { fields } from "./fields/fields.js";

import { ajaxViewModalButton } from './components/ajaxViewModalButton.js';
import { ajaxConfirmDeleteButton } from './components/ajaxConfirmDeleteButton.js';
import { ajaxCloneButton } from './components/ajaxCloneButton.js';
import { ajaxWithUpdateFromView } from './components/ajaxWithUpdateFromView.js';

import { scrollbars } from "./components/scrollbars.js";
import { sidebar } from "./components/sidebar.js";
import { submitActions } from "./components/submitActions.js";
import { wysiwyg } from "./components/wysiwyg.js";
import { alerts } from "./components/alerts.js";
import { datepicker} from "./components/datepicker.js";
import { select } from "./components/select.js";
import { projectDescriptionBlocks } from "./components/projectDescriptionBlocks.js";
import { translateBlocks } from "./components/translateBlocks.js";

import { saveAndShowActiveTab } from "./saveAndShowActiveTab.js";

import { preloader } from "./components/preloader.js";

fields();

ajaxViewModalButton();
ajaxConfirmDeleteButton();
ajaxCloneButton();
ajaxWithUpdateFromView();

scrollbars();
sidebar();
submitActions();
wysiwyg();
alerts();
datepicker();
select();
projectDescriptionBlocks();
translateBlocks();

saveAndShowActiveTab();

preloader();

console.log('all js inited');
