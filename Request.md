# Complete Laravel Request Setup Guide

## Table of Contents
1. Basic Setup
2. Form Request Classes
3. Validation Rules
4. Authorization
5. Custom Error Messages
6. Preparing Data
7. Custom Validation Rules
8. Working with Files
9. Conditional Validation
10. Best Practices

---

## 1. Basic Setup

### Creating Form Requests

```bash
# Basic form request
php artisan make:request SendMessageRequest

# Multiple requests at once
php artisan make:request StoreMessageRequest
php artisan make:request UpdateMessageRequest
php artisan make:request DeleteMessageRequest
```

### Basic Request Structure

```php
// app/Http/Requests/SendMessageRequest.php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendMessageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return false; // Change to true or add logic
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            // Your validation rules
        ];
    }
}
```

### Using in Controllers

```php
namespace App\Http\Controllers;

use App\Http\Requests\SendMessageRequest;
use App\Models\Conversation;

class MessageController extends Controller
{
    public function store(SendMessageRequest $request, Conversation $conversation)
    {
        // Request is automatically validated and authorized
        // If validation fails, automatically returns 422 with errors
        
        $validated = $request->validated(); // Only validated data
        
        $message = $conversation->messages()->create($validated);
        
        return response()->json($message, 201);
    }
}
```

---

## 2. Form Request Classes

### Complete Example

```php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendMessageRequest extends FormRequest
{
    /**
     * Authorization check
     * Runs BEFORE validation
     */
    public function authorize(): bool
    {
        // Check if user can send messages in this conversation
        $conversation = $this->route('conversation');
        
        return $conversation && 
               $conversation->participants->contains(auth()->id());
    }

    /**
     * Validation rules
     */
    public function rules(): array
    {
        return [
            'content' => 'required_without:media|string|max:5000',
            'media' => 'required_without:content|file|mimes:jpg,png,mp4,pdf|max:10240',
            'message_type' => 'required|in:text,image,video,audio,file',
            'reply_to_message_id' => 'nullable|exists:messages,message_id',
        ];
    }

    /**
     * Custom error messages
     */
    public function messages(): array
    {
        return [
            'content.required_without' => 'Please provide either text or media.',
            'media.max' => 'File size cannot exceed 10MB.',
            'message_type.in' => 'Invalid message type.',
        ];
    }

    /**
     * Custom attribute names (for error messages)
     */
    public function attributes(): array
    {
        return [
            'reply_to_message_id' => 'reply message',
        ];
    }

    /**
     * Prepare data for validation
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'sender_id' => auth()->id(),
            'conversation_id' => $this->route('conversation')->conversation_id,
        ]);
    }

    /**
     * Configure validator instance
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Additional validation logic
            if ($this->somethingElseIsInvalid()) {
                $validator->errors()->add('field', 'Something is wrong!');
            }
        });
    }

    /**
     * Handle failed authorization
     */
    protected function failedAuthorization()
    {
        throw new AuthorizationException('You are not authorized to send messages in this conversation.');
    }
}
```

---

## 3. Validation Rules

### Common Validation Rules

```php
public function rules(): array
{
    return [
        // Required
        'field' => 'required',
        'field' => 'nullable', // Optional
        
        // String
        'name' => 'string',
        'name' => 'string|min:3|max:100',
        
        // Numeric
        'age' => 'integer',
        'age' => 'integer|min:18|max:100',
        'price' => 'numeric',
        'rating' => 'numeric|between:0,5',
        
        // Email
        'email' => 'email',
        'email' => 'email:rfc,dns', // Strict validation
        
        // Unique
        'email' => 'unique:users',
        'email' => 'unique:users,email',
        'username' => 'unique:users,username,' . $this->user->id, // Exclude current user
        
        // Exists
        'user_id' => 'exists:users,user_id',
        'conversation_id' => 'exists:conversations,conversation_id',
        
        // Boolean
        'is_active' => 'boolean',
        
        // Date
        'birth_date' => 'date',
        'birth_date' => 'date|before:today',
        'appointment' => 'date|after:tomorrow',
        'event_date' => 'date_format:Y-m-d',
        
        // File
        'avatar' => 'file',
        'avatar' => 'image',
        'avatar' => 'image|mimes:jpg,png|max:2048', // KB
        'document' => 'file|mimes:pdf,docx|max:5120',
        'video' => 'file|mimetypes:video/mp4,video/avi|max:51200',
        
        // URL
        'website' => 'url',
        'website' => 'url|active_url', // Check if reachable
        
        // Array
        'tags' => 'array',
        'tags' => 'array|min:1|max:5',
        'tags.*' => 'string|max:50', // Each element
        
        // In/Not In
        'role' => 'in:admin,user,moderator',
        'status' => 'not_in:banned,suspended',
        
        // Confirmed (password confirmation)
        'password' => 'required|confirmed|min:8',
        // Requires password_confirmation field
        
        // Same/Different
        'password_confirm' => 'same:password',
        'new_email' => 'different:old_email',
        
        // Regex
        'phone' => 'regex:/^[0-9]{10}$/',
        'username' => 'regex:/^[a-zA-Z0-9_]+$/',
        
        // Multiple rules
        'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
    ];
}
```

### Messenger-Specific Examples

```php
// Send Message Request
public function rules(): array
{
    return [
        'content' => 'required_without:media|string|max:5000',
        'media' => 'required_without:content|file|max:10240',
        'message_type' => 'required|in:text,image,video,audio,file',
        'reply_to_message_id' => 'nullable|exists:messages,message_id',
    ];
}

// Update Message Request
public function rules(): array
{
    return [
        'content' => 'required|string|max:5000',
    ];
}

// Create Conversation Request
public function rules(): array
{
    return [
        'type' => 'required|in:direct,group',
        'name' => 'required_if:type,group|string|max:100',
        'description' => 'nullable|string|max:500',
        'participant_ids' => 'required|array|min:1',
        'participant_ids.*' => 'exists:users,user_id|different:' . auth()->id(),
    ];
}

// Update Profile Request
public function rules(): array
{
    return [
        'display_name' => 'required|string|max:100',
        'username' => 'required|string|alpha_dash|unique:users,username,' . auth()->id(),
        'bio' => 'nullable|string|max:500',
        'avatar' => 'nullable|image|mimes:jpg,png|max:2048',
    ];
}

// Register Request
public function rules(): array
{
    return [
        'username' => 'required|string|alpha_dash|min:3|max:50|unique:users',
        'email' => 'required|email|unique:users',
        'password' => 'required|string|min:8|confirmed',
        'display_name' => 'required|string|max:100',
    ];
}
```

---

## 4. Authorization

### Basic Authorization

```php
public function authorize(): bool
{
    // Allow all authenticated users
    return auth()->check();
    
    // Allow specific users
    return auth()->id() === 1;
    
    // Always allow (move authorization to controller/policy)
    return true;
}
```

### Check Route Parameters

```php
public function authorize(): bool
{
    $conversation = $this->route('conversation');
    
    // Check if user is participant
    return $conversation->participants->contains(auth()->id());
}
```

### Check Resource Ownership

```php
public function authorize(): bool
{
    $message = $this->route('message');
    
    // Only message sender can update
    return $message->sender_id === auth()->id();
}
```

### Use Policies

```php
public function authorize(): bool
{
    $conversation = $this->route('conversation');
    
    // Use policy
    return $this->user()->can('sendMessage', $conversation);
}
```

### Complex Authorization

```php
public function authorize(): bool
{
    $conversation = $this->route('conversation');
    $user = auth()->user();
    
    // Must be participant
    if (!$conversation->participants->contains($user->id)) {
        return false;
    }
    
    // Check if user is blocked
    if ($conversation->blocked_users->contains($user->id)) {
        return false;
    }
    
    // Admin always allowed
    if ($user->role === 'admin') {
        return true;
    }
    
    return true;
}
```

### Custom Authorization Error

```php
use Illuminate\Auth\Access\AuthorizationException;

protected function failedAuthorization()
{
    throw new AuthorizationException(
        'You do not have permission to perform this action.'
    );
}

// Or return custom response
protected function failedAuthorization()
{
    abort(403, 'You are not authorized to send messages in this conversation.');
}
```

---

## 5. Custom Error Messages

### Message Method

```php
public function messages(): array
{
    return [
        'email.required' => 'Please enter your email address.',
        'email.email' => 'Please enter a valid email address.',
        'email.unique' => 'This email is already registered.',
        
        'password.required' => 'Password is required.',
        'password.min' => 'Password must be at least :min characters.',
        'password.confirmed' => 'Password confirmation does not match.',
        
        'content.required_without' => 'Please provide either text or media.',
        'media.max' => 'File size cannot exceed :max KB.',
        
        // Wildcard for arrays
        'tags.*.max' => 'Each tag must not exceed :max characters.',
    ];
}
```

### Attributes Method (Field Names)

```php
public function attributes(): array
{
    return [
        'email' => 'email address',
        'password' => 'password',
        'display_name' => 'display name',
        'reply_to_message_id' => 'reply message',
        'participant_ids' => 'participants',
        'participant_ids.*' => 'participant',
    ];
}

// Instead of: "The reply_to_message_id field is required."
// Shows: "The reply message field is required."
```

### Translation Files

```php
// resources/lang/en/validation.php
return [
    'required' => 'The :attribute field is required.',
    'email' => 'The :attribute must be a valid email address.',
    'max' => [
        'string' => 'The :attribute must not be greater than :max characters.',
        'file' => 'The :attribute must not be greater than :max kilobytes.',
    ],
    
    'attributes' => [
        'email' => 'email address',
        'password' => 'password',
    ],
];
```

---

## 6. Preparing Data

### Prepare for Validation

```php
protected function prepareForValidation(): void
{
    // Add data before validation
    $this->merge([
        'sender_id' => auth()->id(),
        'conversation_id' => $this->route('conversation')->conversation_id,
    ]);
    
    // Clean/format data
    $this->merge([
        'phone' => preg_replace('/[^0-9]/', '', $this->phone),
        'email' => strtolower($this->email),
    ]);
}
```

### Modify After Validation

```php
public function validated($key = null, $default = null)
{
    $validated = parent::validated($key, $default);
    
    // Strip HTML tags
    if (isset($validated['content'])) {
        $validated['content'] = strip_tags($validated['content']);
    }
    
    // Add computed values
    $validated['slug'] = str_slug($validated['title']);
    
    return $validated;
}
```

### Pass Through Data

```php
protected function passedValidation(): void
{
    // Runs after validation passes
    // Before controller method
    
    // Example: Set default values
    if (!$this->has('message_type')) {
        $this->merge(['message_type' => 'text']);
    }
}
```

---

## 7. Custom Validation Rules

### Inline Closure Rules

```php
use Illuminate\Validation\Rule;

public function rules(): array
{
    return [
        'username' => [
            'required',
            'string',
            function ($attribute, $value, $fail) {
                if (str_contains($value, 'admin')) {
                    $fail('The username cannot contain "admin".');
                }
            },
        ],
        
        'age' => [
            'required',
            'integer',
            function ($attribute, $value, $fail) {
                if ($value < 18) {
                    $fail('You must be at least 18 years old.');
                }
            },
        ],
    ];
}
```

### Custom Rule Classes

```bash
php artisan make:rule NotProfane
```

```php
// app/Rules/NotProfane.php
namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class NotProfane implements Rule
{
    protected $profaneWords = ['badword1', 'badword2'];
    
    public function passes($attribute, $value)
    {
        foreach ($this->profaneWords as $word) {
            if (str_contains(strtolower($value), $word)) {
                return false;
            }
        }
        return true;
    }
    
    public function message()
    {
        return 'The :attribute contains inappropriate language.';
    }
}

// Usage in Request
use App\Rules\NotProfane;

public function rules(): array
{
    return [
        'content' => ['required', 'string', new NotProfane],
    ];
}
```

### Rule with Parameters

```php
namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class MaxWords implements Rule
{
    protected $maxWords;
    
    public function __construct($maxWords)
    {
        $this->maxWords = $maxWords;
    }
    
    public function passes($attribute, $value)
    {
        return str_word_count($value) <= $this->maxWords;
    }
    
    public function message()
    {
        return "The :attribute must not exceed {$this->maxWords} words.";
    }
}

// Usage
use App\Rules\MaxWords;

public function rules(): array
{
    return [
        'content' => ['required', new MaxWords(100)],
    ];
}
```

### Database-Based Validation

```php
use Illuminate\Validation\Rule;

public function rules(): array
{
    return [
        // Unique except current user
        'email' => [
            'required',
            'email',
            Rule::unique('users')->ignore($this->user->id),
        ],
        
        // Exists in specific column
        'conversation_id' => [
            'required',
            Rule::exists('conversations', 'conversation_id')->where(function ($query) {
                $query->whereHas('participants', function($q) {
                    $q->where('user_id', auth()->id());
                });
            }),
        ],
    ];
}
```

---

## 8. Working with Files

### File Validation

```php
public function rules(): array
{
    return [
        // Basic file
        'document' => 'file',
        
        // Image with type and size
        'avatar' => 'image|mimes:jpg,png,gif|max:2048', // 2MB
        
        // Video
        'video' => 'file|mimetypes:video/mp4,video/avi|max:51200', // 50MB
        
        // Multiple files
        'attachments' => 'array|max:5',
        'attachments.*' => 'file|mimes:pdf,docx|max:10240',
        
        // Image dimensions
        'banner' => 'image|dimensions:min_width=1200,min_height=400',
        'thumbnail' => 'image|dimensions:width=200,height=200',
    ];
}
```

### Accessing Uploaded Files

```php
public function store(SendMessageRequest $request)
{
    $validated = $request->validated();
    
    // Check if file exists
    if ($request->hasFile('media')) {
        $file = $request->file('media');
        
        // Store file
        $path = $file->store('messages', 'public');
        // Or with custom name
        $path = $file->storeAs('messages', 'custom-name.jpg', 'public');
        
        $validated['media_url'] = Storage::url($path);
    }
    
    $message = Message::create($validated);
    
    return response()->json($message);
}
```

### File Information

```php
$file = $request->file('media');

$file->getClientOriginalName();  // Original filename
$file->getClientOriginalExtension(); // File extension
$file->getSize(); // File size in bytes
$file->getMimeType(); // MIME type
$file->isValid(); // Check if upload was successful
```

---

## 9. Conditional Validation

### Required If

```php
public function rules(): array
{
    return [
        // Required if type is 'group'
        'name' => 'required_if:type,group',
        
        // Required if creating group conversation
        'description' => 'required_if:type,group|max:500',
        
        // Required unless type is 'direct'
        'participant_ids' => 'required_unless:type,direct|array',
    ];
}
```

### Required With/Without

```php
public function rules(): array
{
    return [
        // Required if media is present
        'media_type' => 'required_with:media',
        
        // Required if media is not present
        'content' => 'required_without:media',
        
        // Required with all
        'city' => 'required_with_all:street,zip_code',
    ];
}
```

### Required When (Closure)

```php
use Illuminate\Validation\Rule;

public function rules(): array
{
    return [
        'reason' => [
            Rule::requiredIf(function () {
                return $this->type === 'rejection';
            })
        ],
        
        'admin_approval' => [
            Rule::requiredIf(function () {
                return auth()->user()->role !== 'admin';
            })
        ],
    ];
}
```

### Conditional Rules

```php
public function rules(): array
{
    $rules = [
        'content' => 'required|string|max:5000',
        'message_type' => 'required|in:text,image,video,file',
    ];
    
    // Add media validation only if media is present
    if ($this->hasFile('media')) {
        $rules['media'] = 'file|max:10240';
        
        // Different rules based on message type
        if ($this->message_type === 'image') {
            $rules['media'] = 'image|mimes:jpg,png|max:5120';
        } elseif ($this->message_type === 'video') {
            $rules['media'] = 'file|mimetypes:video/mp4|max:51200';
        }
    }
    
    return $rules;
}
```

### Sometimes (Optional Field)

```php
public function rules(): array
{
    return [
        'bio' => 'sometimes|string|max:500',
        // Only validates if field is present in request
        
        'avatar' => 'sometimes|image|max:2048',
    ];
}
```

### Prohibits (Mutual Exclusion)

```php
public function rules(): array
{
    return [
        // Cannot have both media and link
        'media' => 'prohibits:link',
        'link' => 'prohibits:media',
    ];
}
```

---

## 10. Best Practices

### Organize by Feature

```
app/Http/Requests/
├── Auth/
│   ├── LoginRequest.php
│   ├── RegisterRequest.php
│   └── PasswordResetRequest.php
├── Message/
│   ├── SendMessageRequest.php
│   ├── UpdateMessageRequest.php
│   └── DeleteMessageRequest.php
├── Conversation/
│   ├── CreateConversationRequest.php
│   └── UpdateConversationRequest.php
└── User/
    ├── UpdateProfileRequest.php
    └── UpdateSettingsRequest.php
```

### Separate Store and Update Requests

```php
// StoreMessageRequest.php
public function rules(): array
{
    return [
        'content' => 'required|string|max:5000',
        'conversation_id' => 'required|exists:conversations,conversation_id',
        'message_type' => 'required|in:text,image,video',
    ];
}

// UpdateMessageRequest.php
public function rules(): array
{
    return [
        'content' => 'required|string|max:5000',
        // No conversation_id - can't change conversation
    ];
}
```

### Use Array Notation

```php
// ✅ Good - Easy to read
public function rules(): array
{
    return [
        'email' => ['required', 'email', 'unique:users'],
        'password' => ['required', 'min:8', 'confirmed'],
    ];
}

// ❌ Less readable
public function rules(): array
{
    return [
        'email' => 'required|email|unique:users',
        'password' => 'required|min:8|confirmed',
    ];
}
```

### Keep Authorization Simple

```php
// ✅ Good - Simple check
public function authorize(): bool
{
    return $this->route('conversation')
        ->participants
        ->contains(auth()->id());
}

// ❌ Bad - Too complex, move to Policy
public function authorize(): bool
{
    $conversation = $this->route('conversation');
    $user = auth()->user();
    
    if ($conversation->type === 'private' && !$conversation->participants->contains($user->id)) {
        return false;
    }
    
    if ($conversation->is_archived && $user->role !== 'admin') {
        return false;
    }
    
    // 20+ lines of authorization logic...
    
    return true;
}
```

### Document Complex Rules

```php
public function rules(): array
{
    return [
        // Username must be 3-50 chars, alphanumeric with underscores
        'username' => 'required|alpha_dash|min:3|max:50|unique:users',
        
        // Profile picture: JPG/PNG, max 2MB, min 100x100px
        'avatar' => 'image|mimes:jpg,png|max:2048|dimensions:min_width=100,min_height=100',
        
        // At least 1 participant, max 50, each must exist and not be current user
        'participant_ids' => 'required|array|min:1|max:50',
        'participant_ids.*' => 'exists:users,user_id|different:' . auth()->id(),
    ];
}
```

---

## Complete Examples

### Send Message Request

```php
namespace App\Http\Requests\Message;

use Illuminate\Foundation\Http\FormRequest;

class SendMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->route('conversation')
            ->participants
            ->contains(auth()->id());
    }

    public function rules(): array
    {
        return [
            'content' => 'required_without:media|string|max:5000',
            'media' => 'required_without:content|file|max:10240',
            'message_type' => 'required|in:text,image,video,audio,file',
            'reply_to_message_id' => 'nullable|exists:messages,message_id',
        ];
    }

    public function messages(): array
    {
        return [
            'content.required_without' => 'Please provide either text content or media.',
            'media.max' => 'File size cannot exceed 10MB.',
            'message_type.in' => 'Invalid message type.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'sender_id' => auth()->id(),
            'conversation_id' => $this->route('conversation')->conversation_id,
        ]);
    }
}
```

### Create Conversation Request

```php
namespace App\Http\Requests\Conversation;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateConversationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'type' => 'required|in:direct,group',
            'name' => 'required_if:type,group|string|max:100',
            'description' => 'nullable|string|max:500',
            'avatar' => 'nullable|image|mimes:jpg,png|max:2048',
            
            'participant_ids' => [
                'required',
                'array',
                $this->type === 'direct' ? 'size:1' : 'min:2|max:50',
            ],
            'participant_ids.*' => [
                'exists:users,user_id',
                Rule::notIn([auth()->id()]), // Can't add yourself
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required_if' => 'Group conversations must have a name.',
            'participant_ids.size' => 'Direct conversations must have exactly 1 participant.',
            'participant_ids.min' => 'Group conversations must have at least 2 participants.',
            'participant_ids.max' => 'You can add maximum 50 participants.',
        ];
    }

    public function attributes(): array
    {
        return [
            'participant_ids' => 'participants',
        ];
    }
}
```

### Update Profile Request

```php
namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // User can update their own profile
    }

    public function rules(): array
    {
        return [
            'display_name' => 'required|string|max:100',
            
            'username' => [
                'required',
                'string',
                'alpha_dash',
                'min:3',
                'max:50',
                Rule::unique('users')->ignore(auth()->id()),
            ],
            
            'email' => [
                'required',
                'email',
                Rule::unique('users')->ignore(auth()->id()),
            ],
            
            'bio' => 'nullable|string|max:500',
            'phone_number' => 'nullable|regex:/^[0-9]{10}$/',
            'avatar' => 'nullable|image|mimes:jpg,png|max:2048',
        ];
    }

    public function messages(): array
    {
        return [
            'username.alpha_dash' => 'Username can only contain letters, numbers, and underscores.',
            'phone_number.regex' => 'Phone number must be 10 digits.',
        ];
    }

    protected function prepareForValidation(): void
    {
        // Clean phone number
        if ($this->phone_number) {
            $this->merge([
                'phone_number' => preg_replace('/[^0-9]/', '', $this->phone_number),
            ]);
        }
        
        // Lowercase email
        if ($this->email) {
            $this->merge([
                'email' => strtolower($this->email),
            ]);
        }
    }
}
```

### Register Request

```php
namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use App\Rules\NotProfane;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'username' => [
                'required',
                'string',
                'alpha_dash',
                'min:3',
                'max:50',
                'unique:users',
                new NotProfane,
            ],
            
            'email' => 'required|email|unique:users|max:255',
            
            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/',
            ],
            
            'display_name' => 'required|string|max:100',
            
            'terms' => 'accepted',
        ];
    }

    public function messages(): array
    {
        return [
            'username.alpha_dash' => 'Username can only contain letters, numbers, and underscores.',
            'password.regex' => 'Password must contain at least one uppercase letter, one lowercase letter, and one number.',
            'terms.accepted' => 'You must accept the terms and conditions.',
        ];
    }
}
```

---

## Quick Reference

### Artisan Commands
```bash
php artisan make:request SendMessageRequest       # Create request
php artisan make:rule NotProfane                  # Create validation rule
```
