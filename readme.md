# MCP - Model Context Protocol for Joomla!

![Joomla](https://img.shields.io/badge/Joomla!-4.x-5091CD?style=for-the-badge&logo=joomla)
![License](https://img.shields.io/badge/License-GPL--2.0--or--later-blue?style=for-the-badge)
![Version](https://img.shields.io/badge/Version-1.0.0-green?style=for-the-badge)

MCP is a lightweight, powerful system plugin for Joomla that provides a **Model Context Protocol**—a simple, task-based API for managing content. It acts as a streamlined bridge between your Joomla website and the modern world of AI, workflow automation, and internal tooling. Learn more at [Model Context Protocol](https://modelcontextprotocol.io/).

## The Problem

Joomla's built-in REST API is comprehensive, but its complexity can be a hurdle for rapid integration. Modern AI agents and workflow automation platforms thrive on simple, predictable "tools" or endpoints to perform specific actions. They need a clear protocol to interact with external data sources without requiring complex, service-specific configurations.

## The Solution: MCP (Model Context Protocol)

MCP establishes a simple protocol for AI models and services to interact with your Joomla content. It provides a flat, single-endpoint API where an action is specified by a `task` parameter. This design makes it incredibly easy for any application to create, update, or retrieve articles and categories—providing and receiving *context* through a standardized protocol.

It authenticates using Joomla's native API Token system, ensuring that all operations are secure and respect the permission levels of the user associated with the token.

### Key Features

* **Simple Protocol:** No complex RESTful routes to learn. Just one URL and a `task` parameter.
* **Secure:** Leverages Joomla's core `Web Services - Authentication - Token` system.
* **Lightweight:** A single plugin with no external dependencies.
* **AI-Ready:** Designed to serve as the perfect "tool" for AI agents and automation workflows to read from and write to your Joomla database.
* **Essential Endpoints:** Covers the most common content management tasks.

## The AI & Workflow Automation Superpower

This is where MCP truly shines. It transforms your Joomla CMS from a siloed platform into a dynamic, integrated component of your automated workflows and AI-driven content pipelines.

### **For Workflow Automation ([Make.com](https://www.make.com), [n8n](https://n8n.io), [Zapier](https://zapier.com), etc.)**

Platforms like [Make.com](https://www.make.com) and [n8n](https://n8n.io) are built around connecting services through API calls. MCP provides the perfect endpoints for their generic "HTTP Request" modules.

> **Example Use Case:**
> An RSS feed triggers a workflow in **n8n**. An AI node rewrites the content, and then an HTTP Request node uses the **Model Context Protocol** (`task=mcp.create_article`) to instantly publish the new article to your Joomla site.

### **For AI Agent Frameworks ([crewAI](https://crew.ai), [AutoGen](https://autogen.io), etc.)**

AI agents need "tools" to interact with the real world. MCP's endpoints are the perfect building blocks for these tools, allowing agents to autonomously manage content.

> **Example Use Case:**
> You task a **crewAI** agent with "Write a blog post about the latest AI trends and publish it to our website."
> 1.  The `ResearcherAgent` browses the web.
> 2.  The `WriterAgent` composes the article.
> 3.  The `PublisherAgent` is given a `publish_to_joomla` tool, which uses the **Model Context Protocol** to call the `mcp.create_article` endpoint. The AI crew completes the entire task without human intervention.

### **For Internal Tooling & Scripting ([Windmill](https://windmill.dev), [Superblocks](https://superblocks.com), etc.)**

Platforms like [Windmill](https://windmill.dev) allow you to quickly build internal admin panels and run scripts. MCP provides a clean abstraction layer for interacting with Joomla.

> **Example Use Case:**
> Your marketing team wants a simple dashboard in **Windmill** to quickly publish press releases. A developer creates a simple UI. The "Publish" button triggers a script that uses the **Model Context Protocol** to push the content live instantly.

## Installation & Setup

1.  **Download:** Download the latest `plg_system_mcp_vX.X.X.zip` file from the [Releases](https://github.com/your-username/joomla-mcp-plugin/releases) page.
2.  **Install:** In your Joomla Administrator panel, go to `System` -> `Install` -> `Extensions` and upload the zip file.
3.  **Enable Plugin:** Go to `System` -> `Manage` -> `Plugins` and search for "MCP". Enable the plugin.
4.  **Enable Joomla API Auth:**
    * Go to `System` -> `Manage` -> `Plugins` and search for `Web Services - Authentication - Token`. Ensure it is enabled.
5.  **Generate a User API Token:**
    * Go to `Users` -> `Manage` and select the user you want to grant API access to. This user's permissions will be respected.
    * Click the **"Joomla API Token"** tab.
    * Click **"Create a New Token"** to generate an API key. Copy this key securely.

## API Usage

> **Note:** See the [Critical Note for Version 2](#a-critical-note-for-version-2) below for important planned changes to the API.

* **Endpoint:** `https://www.yoursite.com/index.php`
* **Method:** `POST`
* **Authentication Header:** `X-Joomla-Token: YOUR_JOOMLA_API_TOKEN`
* **Query Parameter:** `task=mcp.your_task`

### Available Tasks

| Task                         | Description                                            | Example JSON Body                                                                                             |
| ---------------------------- | ------------------------------------------------------ | ------------------------------------------------------------------------------------------------------------- |
| `mcp.get_article`           | Retrieves a single article by ID.                      | `{"article_id": 124}`                                                                                         |
| `mcp.get_articles`           | Retrieves a list of all articles.                      | `null`                                                                                                        |
| `mcp.get_categories`         | Retrieves a list of all content categories.            | `null`                                                                                                        |
| `mcp.create_article`         | Creates a new article.                                 | `{"title": "My Title", "articletext": "<p>Content</p>", "catid": 2, "published": true}`                       |
| `mcp.update_article`         | Updates an existing article.                           | `{"article_id": 124, "title": "Updated Title", "articletext": "<p>New content.</p>"}`                       |
| `mcp.manage_article_state`   | Changes an article's state (1=Pub, 0=Unpub, 2=Archive) | `{"article_id": 125, "target_state": 0}`                                                                      |
| `mcp.move_article_to_trash`  | Moves an article to the trash.                         | `{"article_id": 126}`                                                                                         |

### Example: Full `curl` Request

Here's how to create a new, published article in category ID `8`.

```bash
curl -X POST \
  -H "X-Joomla-Token: YOUR_JOOMLA_API_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"title": "New Article via MCP", "articletext": "<p>This content was published by an automated workflow!</p>", "catid": 8, "published": true}' \
  "[https://www.yoursite.com/index.php?task=mcp.create_article](https://www.yoursite.com/index.php?task=mcp.create_article)"
```

### Setting Up MCP in Claude Desktop

Add the following configuration to your `claude_desktop_config.json` file:

```json
{
  "mcpServers": {
    "Joomla Articles MCP": {
      "command": "{{PATH_TO_UV}}",
      "args": [
        "--directory",
        "{{PATH_TO_PROJECT}}",
        "run",
        "main.py"
      ],
      "env": {
        "JOOMLA_BASE_URL": "<your_joomla_website_url>",
        "BEARER_TOKEN": "<your_joomla_api_token>"
      }
    }
  }
}
```

* Replace `{{PATH_TO_UV}}` with the path to `uv` (you can find it by running `which uv`).
* Replace `{{PATH_TO_PROJECT}}` with the path to your project directory (use `pwd` in the repository root to get the path).
* Replace `<your_joomla_website_url>` with your Joomla website's base URL.
* Replace `<your_joomla_api_token>` with the API token generated in Joomla.

### Setting Up MCP in n8n

1. **Create an HTTP Node:**

   * Add an HTTP Request node to your workflow.
   * Set the **Method** to `POST`.
   * Set the **URL** to `https://<your_joomla_website_url>/index.php`.
   * Add a query parameter: `task=mcp.<your_task>` (e.g., `task=mcp.create_article`).

2. **Add Headers:**

   * Add a header: `X-Joomla-Token` with the value `<your_joomla_api_token>`.

3. **Add JSON Body:**

   * Add the JSON payload for the task you want to perform (e.g., creating or updating an article).

### Setting Up MCP in Make (formerly Integromat)

1. **Create a Scenario:**

   * Add an HTTP module to your scenario.
   * Set the **Method** to `POST`.
   * Set the **URL** to `https://<your_joomla_website_url>/index.php`.

2. **Add Query Parameters:**

   * Add a query parameter: `task=mcp.<your_task>` (e.g., `task=mcp.get_article`).

3. **Add Headers:**

   * Add a header: `X-Joomla-Token` with the value `<your_joomla_api_token>`.

4. **Add JSON Body:**

   * Add the JSON payload for the task you want to perform.

## A Critical Note for Version 2

I was so eager to get this out that I did not think through the tasks as carefully as I should have. Why does managing the article state or moving it to trash exist as separate tasks from `update_article`?

### Planned Changes for Version 2

1. **Task Consolidation:**
   * The `manage_article_state` and `move_article_to_trash` tasks will be consolidated into the `update_article` task. This will simplify the API and make it more intuitive.

2. **New Features:**
   * Additional tasks for managing categories and tags will be introduced, expanding the plugin's capabilities.