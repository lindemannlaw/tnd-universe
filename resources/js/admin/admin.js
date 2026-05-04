import '../bootstrap';
import * as bootstrap from 'bootstrap';
window.bootstrap = bootstrap;

import { fields } from "./fields/fields.js";

import { ajaxViewModalButton } from './components/ajaxViewModalButton.js';
import { ajaxConfirmDeleteButton } from './components/ajaxConfirmDeleteButton.js';
import { ajaxCloneButton } from './components/ajaxCloneButton.js';
import { ajaxWithUpdateFromView } from './components/ajaxWithUpdateFromView.js';
import { autoOpenEditModal } from './components/autoOpenEditModal.js';
import { mediaPickerModal } from './components/mediaPickerModal.js';
import { linkMediaPickerField } from './components/linkMediaPickerField.js';
import { imageFieldPicker } from './components/imageFieldPicker.js';
import { linkImageGenerator } from './components/linkImageGenerator.js';
import { stackedModalFix } from './components/stackedModalFix.js';
import { libraryUpload } from './components/libraryUpload.js';

import { scrollbars } from "./components/scrollbars.js";
import { sidebar } from "./components/sidebar.js";
import { submitActions } from "./components/submitActions.js";
import { wysiwyg } from "./components/wysiwyg.js";
import { alerts } from "./components/alerts.js";
import { datepicker} from "./components/datepicker.js";
import { select } from "./components/select.js";
import { projectDescriptionBlocks } from "./components/projectDescriptionBlocks.js";
import { translateBlocks } from "./components/translateBlocks.js";
import { autoTranslateOverlay } from "./components/autoTranslateOverlay.js";

import { saveAndShowActiveTab } from "./saveAndShowActiveTab.js";

import { preloader } from "./components/preloader.js";

fields();

ajaxViewModalButton();
ajaxConfirmDeleteButton();
ajaxCloneButton();
ajaxWithUpdateFromView();
autoOpenEditModal();
mediaPickerModal();
linkMediaPickerField();
imageFieldPicker();
linkImageGenerator();
stackedModalFix();
libraryUpload();

scrollbars();
sidebar();
submitActions();
wysiwyg();
alerts();
datepicker();
select();
projectDescriptionBlocks();
translateBlocks();
autoTranslateOverlay();

saveAndShowActiveTab();

preloader();
