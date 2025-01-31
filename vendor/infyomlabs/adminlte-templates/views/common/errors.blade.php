@if(!empty($errors))
@if ($errors->any())                                                
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <strong>¡Revise los campos!</strong>                        
             @foreach ($errors->all() as $error)                                    
                 <span class="badge text-bg-light">{{ $error }}</span>
             @endforeach                        
             <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif
@endif
