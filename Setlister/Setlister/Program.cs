using MudBlazor.Services;
using Setlister.Client.Pages;
using Setlister.Components;
using Microsoft.AspNetCore.Mvc;
using System.Net.Http;

var builder = WebApplication.CreateBuilder(args);

// Register services for the backend
builder.Services.AddControllers();

// Register HttpClient for Dependency Injection (for backend API calls)
builder.Services.AddHttpClient();

// Register controllers with views
builder.Services.AddControllersWithViews(); // Ensure controllers are added

// Configure CORS for Blazor Client (adjust URL for your Blazor client)
builder.Services.AddCors(options =>
{
    options.AddPolicy("AllowBlazorClient",
        policy => policy.WithOrigins("https://localhost:7029") // Change to your Blazor Client URL
                        .AllowAnyMethod()
                        .AllowAnyHeader());
});

// Add MudBlazor services
builder.Services.AddMudServices();

// Add services for Razor Components (interactive WebAssembly)
builder.Services.AddRazorComponents()
    .AddInteractiveWebAssemblyComponents();

// Add Anti-forgery services and configure options if needed
builder.Services.AddAntiforgery(options =>
{
    // Optionally set the header name for anti-forgery token (default is 'X-XSRF-TOKEN')
    options.HeaderName = "X-XSRF-TOKEN"; // Set the header name for anti-forgery token
});

var app = builder.Build();

// Configure the HTTP request pipeline.
if (app.Environment.IsDevelopment())
{
    app.UseWebAssemblyDebugging();
}
else
{
    app.UseExceptionHandler("/Error", createScopeForErrors: true);
    // The default HSTS value is 30 days. You may want to change this for production scenarios, see https://aka.ms/aspnetcore-hsts.
    app.UseHsts();
}

// Enable HTTPS redirection
app.UseHttpsRedirection();

// Serve static files (e.g., images, CSS, JS)
app.UseStaticFiles();

// Use Anti-forgery middleware (after app.UseRouting and before app.MapControllers)
app.UseRouting();

// Add Anti-forgery protection middleware
app.UseAntiforgery();

// Map Razor Components (interactive WebAssembly)
app.MapRazorComponents<App>()
    .AddInteractiveWebAssemblyRenderMode()
    .AddAdditionalAssemblies(typeof(Setlister.Client._Imports).Assembly);

app.UseEndpoints(endpoints =>
{
    // Define the default route
    endpoints.MapControllerRoute(
        name: "default",
        pattern: "{controller=Home}/{action=Index}/{id?}");
});

// Map controller routes (including /spotify/login)
app.MapControllers();

// Start the app
app.Run();