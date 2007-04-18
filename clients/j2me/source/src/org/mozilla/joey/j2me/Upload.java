package org.mozilla.joey.j2me;

public class Upload
{
	private String name;
	private String description;
	private String data;
	
	public Upload(String name, String description, String data)
	{
		this.name = name;
		this.description = description;
		this.data = data;
	}

	public String getData()
	{
		return this.data;
	}

	public void setData(String data)
	{
		this.data = data;
	}

	public String getDescription()
	{
		return this.description;
	}

	public void setDescription(String description)
	{
		this.description = description;
	}

	public String getName()
	{
		return this.name;
	}

	public void setName(String name)
	{
		this.name = name;
	}
}
