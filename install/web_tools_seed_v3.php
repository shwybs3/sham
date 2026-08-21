<?php
/**
 * Third-batch seed: high-demand, fully-functional (no AI dependency) Arabic
 * utility tools. Unlike the AI-tool batches, these run entirely client-side
 * with real deterministic logic (date math, Arabic grammar rules, keyboard
 * layout tables) — no risk of AI 500s, immediately functional on publish.
 */
return [
  ['slug'=>'hijri-date','title'=>'محوّل التاريخ الهجري والميلادي','desc'=>'حوّل أي تاريخ بين الهجري والميلادي فوراً وبدقة رياضية كاملة','icon'=>'<rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>','color'=>'#0f766e','bg'=>'#ccfbf1'],
  ['slug'=>'number-to-words','title'=>'تحويل الأرقام إلى كتابة بالعربي','desc'=>'حوّل أي رقم إلى كتابته الصحيحة بالحروف العربية — للشيكات والفواتير','icon'=>'<path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/>','color'=>'#b45309','bg'=>'#fef3c7'],
  ['slug'=>'tashkeel-remove','title'=>'إزالة التشكيل من النص العربي','desc'=>'احذف الحركات والتشكيل من أي نص عربي بضغطة واحدة','icon'=>'<path d="M4 7V4h16v3"/><path d="M9 20h6"/><path d="M12 4v16"/>','color'=>'#7c3aed','bg'=>'#ede9fe'],
  ['slug'=>'keyboard-fix','title'=>'تصحيح الكتابة الخاطئة (عربي/إنجليزي)','desc'=>'صحّح النص المكتوب بلوحة مفاتيح خاطئة بين العربي والإنجليزي فوراً','icon'=>'<rect x="2" y="6" width="20" height="12" rx="2"/><path d="M6 10h.01M10 10h.01M14 10h.01M18 10h.01M6 14h12"/>','color'=>'#dc2626','bg'=>'#fee2e2'],
  ['slug'=>'zakat-calc','title'=>'حاسبة الزكاة','desc'=>'احسب زكاة مالك ومدخراتك وذهبك بدقة وفق النصاب الشرعي','icon'=>'<path d="M12 2v20M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/>','color'=>'#15803d','bg'=>'#dcfce7'],
];
