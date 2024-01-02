{{-- ---------------------- Group Channel Modal ---------------------- --}}
<div class="app-modal group-modal" data-name="addGroup">
    <div class="app-modal-container">
        <div class="app-modal-card" data-name="addGroup" data-modal='0'>
            <form id="addGroupForm" action="{{ route('group-chat.create') }}" enctype="multipart/form-data" method="POST">
                @csrf
                <div class="app-modal-header">
                    Create a Group Channel
                </div>
                <div class="app-modal-body">
                    {{-- channel avatar --}}
                    <div class="avatar av-l upload-avatar-preview chatify-d-flex"
                         style="background-image: url('{{ Chatify::getUserWithAvatar(Auth::user())->avatar }}');"
                    ></div>
                    <p class="upload-avatar-details"></p>
                    <label class="app-btn a-btn-primary update" style="background-color:{{$messengerColor}}">
                        Upload New
                        <input class="upload-avatar chatify-d-none" accept="image/*" name="avatar" type="file" />
                    </label>
                    {{-- End channel avatar --}}

                    <div class="form-control">
                        <label class="form-label" for="group_name">Group Name</label>
                        <input class="form-input" type="text" name="group_name" id="group_name"
                               placeholder="Enter a name"
                               required="required"
                        />
                    </div>
                    <div class="form-control">
                        <label class="form-label" for="search">Select user(s) to the group</label>
                        <input class="form-input user-search" type="text" name="search" placeholder="Search"/>
                    </div>
                    <div class="search-records app-scroll users-list"></div>
                    <div style="margin-top: 1rem; margin-bottom: 2rem">
                        <label class="form-label">Added Users</label>
                        <div class="added-users app-scroll users-list"></div>
                    </div>
                </div>
                <div class="app-modal-footer">
                    <a href="javascript:void(0)" class="app-btn cancel">Cancel</a>
                    <input type="submit" class="app-btn a-btn-success update" value="Save Changes" />
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ---------------------- Delete Group Chat Modal ---------------------- --}}
<div class="app-modal" data-name="delete-group">
    <div class="app-modal-container">
          <div class="app-modal-card" data-name="delete-group" data-modal='0'>
            <div class="app-modal-header">Are you sure you want to delete this group?</div>
            <div class="app-modal-body">You can not undo this action</div>
            <div class="app-modal-footer">
                <a href="javascript:void(0)" class="app-btn cancel">Cancel</a>
                <a href="javascript:void(0)" class="app-btn a-btn-danger delete">Delete</a>
            </div>
        </div>
    </div>
</div>

{{-- ---------------------- Leave Group Chat Modal ---------------------- --}}
<div class="app-modal" data-name="leave-group">
    <div class="app-modal-container">
          <div class="app-modal-card" data-name="leave-group" data-modal='0'>
            <div class="app-modal-header">Are you sure you want to leave this group?</div>
            <div class="app-modal-body">You can not undo this action</div>
            <div class="app-modal-footer">
                <a href="javascript:void(0)" class="app-btn cancel">Cancel</a>
                <a href="javascript:void(0)" class="app-btn a-btn-danger delete">Leave</a>
            </div>
        </div>
    </div>
</div>

{{-- ---------------------- Image modal box ---------------------- --}}
<div id="imageModalBox" class="imageModal">
    <span class="imageModal-close">&times;</span>
    <img class="imageModal-content" id="imageModalBoxSrc">
  </div>

  {{-- ---------------------- Delete Modal ---------------------- --}}
  <div class="app-modal" data-name="delete">
      <div class="app-modal-container">
          <div class="app-modal-card" data-name="delete" data-modal='0'>
              <div class="app-modal-header">Are you sure you want to delete this?</div>
              <div class="app-modal-body">You can not undo this action</div>
              <div class="app-modal-footer">
                  <a href="javascript:void(0)" class="app-btn cancel">Cancel</a>
                  <a href="javascript:void(0)" class="app-btn a-btn-danger delete">Delete</a>
              </div>
          </div>
      </div>
  </div>
  {{-- ---------------------- Alert Modal ---------------------- --}}
  <div class="app-modal" data-name="alert">
      <div class="app-modal-container">
          <div class="app-modal-card" data-name="alert" data-modal='0'>
              <div class="app-modal-header"></div>
              <div class="app-modal-body"></div>
              <div class="app-modal-footer">
                  <a href="javascript:void(0)" class="app-btn cancel">Cancel</a>
              </div>
          </div>
      </div>
  </div>
  {{-- ---------------------- Settings Modal ---------------------- --}}
  <div class="app-modal" data-name="settings">
      <div class="app-modal-container">
          <div class="app-modal-card" data-name="settings" data-modal='0'>
              <form id="update-settings" action="{{ route('avatar.update') }}" enctype="multipart/form-data" method="POST">
                  @csrf
                  {{-- <div class="app-modal-header">Update your profile settings</div> --}}
                  <div class="app-modal-body">
                      {{-- Udate profile avatar --}}
                      <div class="avatar av-l upload-avatar-preview chatify-d-flex"
                      style="background-image: url('{{ Chatify::getUserWithAvatar(Auth::user())->avatar }}');"
                      ></div>
                      <p class="upload-avatar-details"></p>
                      <label class="app-btn a-btn-primary update" style="background-color:{{$messengerColor}}">
                          Upload New
                          <input class="upload-avatar chatify-d-none" accept="image/*" name="avatar" type="file" />
                      </label>
                      {{-- Dark/Light Mode  --}}
                      <p class="divider"></p>
                      <p class="app-modal-header">Dark Mode <span class="
                        {{ Auth::user()->dark_mode > 0 ? 'fas' : 'far' }} fa-moon dark-mode-switch"
                         data-mode="{{ Auth::user()->dark_mode > 0 ? 1 : 0 }}"></span></p>
                      {{-- change messenger color  --}}
                      <p class="divider"></p>
                      {{-- <p class="app-modal-header">Change {{ config('chatify.name') }} Color</p> --}}
                      <div class="update-messengerColor">
                      @foreach (config('chatify.colors') as $color)
                        <span style="background-color: {{ $color}}" data-color="{{$color}}" class="color-btn"></span>
                        @if (($loop->index + 1) % 5 == 0)
                            <br/>
                        @endif
                      @endforeach
                      </div>
                  </div>
                  <div class="app-modal-footer">
                      <a href="javascript:void(0)" class="app-btn cancel">Cancel</a>
                      <input type="submit" class="app-btn a-btn-success update" value="Save Changes" />
                  </div>
              </form>
          </div>
      </div>
  </div>
