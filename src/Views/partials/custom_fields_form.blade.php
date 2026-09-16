{{-- Dynamic Custom Fields Universal Form Partial --}}
@if(!empty($customFields))
    <div style="margin-top:1.5rem; padding-top:1.5rem; border-top:1px solid var(--border);">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
            <h3 style="font-size:0.95rem; font-weight:700; margin:0; color:var(--primary);">
                🏷️ {{ __('app.settings.custom_fields.title') }}
            </h3>
            <a href="/settings/custom-fields" target="_blank" style="font-size:0.75rem; color:var(--text-dim); text-decoration:none;">
                ⚙️ Manage Custom Fields ↗
            </a>
        </div>
        <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(280px, 1fr)); gap:1rem;">
            @foreach($customFields as $cf)
                @php
                    $code = $cf['code'];
                    $type = $cf['type'];
                    $fieldVal = $values[$code] ?? ($values[$cf['id']] ?? '');
                @endphp
                <div class="form-group" style="{{ $type === 'textarea' ? 'grid-column: 1 / -1;' : '' }}">
                    <label class="form-label" style="font-weight:600; display:flex; justify-content:space-between;">
                        <span>{{ $cf['name'] }}</span>
                        <span style="font-size:0.7rem; font-weight:normal; color:var(--text-dim);"><code>cf_{{ $code }}</code></span>
                    </label>

                    @if($type === 'select' && !empty($cf['options']))
                        @php $opts = is_array($cf['options']) ? $cf['options'] : (json_decode((string)$cf['options'], true) ?? []); @endphp
                        <select name="cf_{{ $code }}" class="form-control">
                            <option value="">-- {{ __('app.common.select_all') ?? 'Select' }} --</option>
                            @foreach($opts as $opt)
                                <option value="{{ $opt }}" @selected((string)$fieldVal === (string)$opt)>{{ $opt }}</option>
                            @endforeach
                        </select>
                    @elseif($type === 'multi_select' && !empty($cf['options']))
                        @php 
                            $opts = is_array($cf['options']) ? $cf['options'] : (json_decode((string)$cf['options'], true) ?? []);
                            $selectedArr = is_array($fieldVal) ? $fieldVal : (json_decode((string)$fieldVal, true) ?? array_map('trim', explode(',', (string)$fieldVal)));
                        @endphp
                        <div style="display:flex; flex-wrap:wrap; gap:0.5rem; background:var(--bg-main); padding:0.5rem; border:1px solid var(--border); border-radius:6px;">
                            @foreach($opts as $opt)
                                <label style="display:inline-flex; align-items:center; gap:0.3rem; font-size:0.8rem; cursor:pointer; margin:0;">
                                    <input type="checkbox" name="cf_{{ $code }}[]" value="{{ $opt }}" @checked(in_array($opt, $selectedArr, true))>
                                    {{ $opt }}
                                </label>
                            @endforeach
                        </div>
                    @elseif($type === 'boolean')
                        <select name="cf_{{ $code }}" class="form-control">
                            <option value="">-- Select --</option>
                            <option value="1" @selected((string)$fieldVal === '1' || $fieldVal === true)>✅ Yes / Active</option>
                            <option value="0" @selected((string)$fieldVal === '0' || $fieldVal === false)>❌ No / Inactive</option>
                        </select>
                    @elseif($type === 'textarea')
                        <textarea name="cf_{{ $code }}" class="form-control" rows="3" placeholder="Enter notes or multi-line details...">{{ $fieldVal }}</textarea>
                    @elseif($type === 'currency')
                        <div style="display:flex; align-items:center;">
                            <span style="background:var(--bg-subtle); border:1px solid var(--border); border-right:none; padding:0.45rem 0.75rem; border-radius:6px 0 0 6px; font-weight:600; color:var(--text-dim);">$</span>
                            <input type="number" step="0.01" name="cf_{{ $code }}" value="{{ $fieldVal }}" class="form-control" style="border-radius:0 6px 6px 0;" placeholder="0.00">
                        </div>
                    @elseif($type === 'number')
                        <input type="number" step="any" name="cf_{{ $code }}" value="{{ $fieldVal }}" class="form-control" placeholder="0">
                    @elseif($type === 'rating')
                        <select name="cf_{{ $code }}" class="form-control">
                            <option value="">-- No Rating --</option>
                            @for($star = 5; $star >= 1; $star--)
                                <option value="{{ $star }}" @selected((int)$fieldVal === $star)>
                                    {{ str_repeat('★', $star) . str_repeat('☆', 5 - $star) }} ({{ $star }}/5)
                                </option>
                            @endfor
                        </select>
                    @elseif($type === 'date')
                        <input type="date" name="cf_{{ $code }}" value="{{ $fieldVal }}" class="form-control">
                    @elseif($type === 'email')
                        <input type="email" name="cf_{{ $code }}" value="{{ $fieldVal }}" class="form-control" placeholder="name@domain.com">
                    @elseif($type === 'phone')
                        <input type="tel" name="cf_{{ $code }}" value="{{ $fieldVal }}" class="form-control" placeholder="+1-555-0100">
                    @elseif($type === 'url')
                        <input type="url" name="cf_{{ $code }}" value="{{ $fieldVal }}" class="form-control" placeholder="https://example.com">
                    @else
                        <input type="text" name="cf_{{ $code }}" value="{{ $fieldVal }}" class="form-control">
                    @endif
                </div>
            @endforeach
        </div>
    </div>
@endif
