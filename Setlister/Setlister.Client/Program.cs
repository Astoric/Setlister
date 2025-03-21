﻿using Microsoft.AspNetCore.Components.WebAssembly.Hosting;
using MudBlazor.Services;
using Setlister.Client.Services;
using System.Net.Http;

var builder = WebAssemblyHostBuilder.CreateDefault(args);

builder.Services.AddScoped(sp => new HttpClient { BaseAddress = new Uri("https://localhost:7028/") });

builder.Services.AddMudServices();

builder.Services.AddScoped<SpotifyService>();

await builder.Build().RunAsync();